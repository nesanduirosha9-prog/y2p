function assignLowestWorkloadForWeek() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const mainSheet = ss.getSheetByName("Main");
  const nameListSheet = ss.getSheetByName("NameLists");

  if (!mainSheet || !nameListSheet) {
    Logger.log("Main or NameLists sheet not found! Check sheet names.");
    return;
  }

  const leaveMap = getLeaveMap();  // NEW: load leave data

  const mainData = mainSheet.getDataRange().getValues();
  const header = mainData[0];

  const dateCol = header.indexOf("Date");
  const timeCol = header.indexOf("Time");
  const assigneeCol = header.indexOf("Assignee");
  const countCol = header.indexOf("Count");
  const nameCol = header.indexOf("Name");

  if ([dateCol, timeCol, assigneeCol, countCol, nameCol].some(c => c === -1)) {
    Logger.log("One or more required columns not found. Check your headers.");
    return;
  }

  const lastRowNameList = nameListSheet.getLastRow();
  if (lastRowNameList < 2) {
    Logger.log("No data in NameLists!");
    return;
  }

  const nameListData = nameListSheet.getRange(2, 2, lastRowNameList - 1, 6).getValues(); // B to G
  const activeNames = nameListData
    .filter(row => row[5] === true)
    .map(row => row[0]);

  if (activeNames.length === 0) {
    Logger.log("No active people found in NameLists.");
    return;
  }

  let taskCounts = {};
  for (let i = 1; i < mainData.length; i++) {
    const assigneesStr = mainData[i][assigneeCol];
    if (!assigneesStr) continue;
    const assignees = typeof assigneesStr === 'string' ? assigneesStr.split(',').map(n => n.trim()) : [];
    assignees.forEach(name => {
      taskCounts[name] = (taskCounts[name] || 0) + 1;
    });
  }

  let updatedRows = 0;

  function getAvailabilityMapForDay(dayName) {
    const daySheet = ss.getSheetByName(dayName);
    if (!daySheet) {
      Logger.log(`Sheet for ${dayName} not found!`);
      return null;
    }

    const dayData = daySheet.getDataRange().getValues();
    const timeSlots = dayData[0].slice(1); // skip first col

    let availabilityMap = {};
    for (let i = 1; i < dayData.length; i++) {
      const name = dayData[i][0];
      if (!activeNames.includes(name)) continue;

      availabilityMap[name] = {};
      for (let j = 1; j < dayData[i].length; j++) {
        availabilityMap[name][timeSlots[j - 1]] = dayData[i][j] === true;
      }
    }
    return availabilityMap;
  }

  for (let i = 1; i < mainData.length; i++) {
    const dateVal = mainData[i][dateCol];
    const timeCell = mainData[i][timeCol];
    const countNeeded = mainData[i][countCol];
    const currentAssignee = mainData[i][assigneeCol];

    if (!dateVal || !timeCell || !countNeeded || currentAssignee) continue;

    let dateObj = new Date(dateVal);
    if (isNaN(dateObj.getTime())) {
      Logger.log(`Invalid date at row ${i + 1}: ${dateVal}`);
      continue;
    }

    let dayName = dateObj.toLocaleString('en-US', { weekday: 'long' });

    if (!["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"].includes(dayName)) {
      continue;
    }

    let availabilityMap = getAvailabilityMapForDay(dayName);
    if (!availabilityMap) {
      Logger.log(`Skipping row ${i + 1} because availability for ${dayName} not found.`);
      continue;
    }

    const timeSlots = typeof timeCell === 'string' ? timeCell.split(',').map(t => t.trim()) : [timeCell];
    Logger.log(`Row ${i + 1} (${dayName}) → Checking availability for time slots: ${timeSlots.join(', ')}`);

    let availablePeople = activeNames.filter(name => {
      return timeSlots.every(slot => availabilityMap[name] && availabilityMap[name][slot] === true);
    });

    // NEW: skip people who are on leave
    const dateStr = dateObj.toDateString();
    if (leaveMap[dateStr]) {
      availablePeople = availablePeople.filter(name => !leaveMap[dateStr].has(name));
    }

    if (availablePeople.length === 0) {
      Logger.log(`No available people found for time slots [${timeSlots.join(', ')}] on ${dayName} at row ${i + 1}`);
      continue;
    }

    availablePeople.sort((a, b) => (taskCounts[a] || 0) - (taskCounts[b] || 0));
    const selected = availablePeople.slice(0, countNeeded);

    mainSheet.getRange(i + 1, assigneeCol + 1).setValue(selected.join(", "));

    selected.forEach(name => {
      taskCounts[name] = (taskCounts[name] || 0) + 1;
    });

    Logger.log(`Assigned [${selected.join(', ')}] for row ${i + 1}`);
    updatedRows++;
  }

  Logger.log(` Assignment complete. Total updated rows: ${updatedRows}`);
}

// // NEW helper function to build the leave map from the Leaves sheet
// function getLeaveMap() {
//   const ss = SpreadsheetApp.getActiveSpreadsheet();
//   const leaveSheet = ss.getSheetByName("Leaves");
//   if (!leaveSheet) {
//     Logger.log("Leaves sheet not found!");
//     return {};
//   }

//   const leaveData = leaveSheet.getDataRange().getValues();
//   let leaveMap = {};
//   for (let i = 1; i < leaveData.length; i++) {
//     const name = leaveData[i][0];
//     const date = leaveData[i][1];
//     if (!name || !date) continue;
//     const dateStr = new Date(date).toDateString();
//     if (!leaveMap[dateStr]) leaveMap[dateStr] = new Set();
//     leaveMap[dateStr].add(name);
//   }
//   return leaveMap;
// }


function getLeaveMap() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const leaveSheet = ss.getSheetByName("Leaves");
  if (!leaveSheet) {
    Logger.log("Leaves sheet not found!");
    return {};
  }

  const leaveData = leaveSheet.getDataRange().getValues();
  let leaveMap = {};

  for (let i = 1; i < leaveData.length; i++) {
    const name = leaveData[i][0]; // Column A: Name
    const fromDate = leaveData[i][1]; // Column B: From Date
    const toDate = leaveData[i][2];   // Column C: To Date (new)

    if (!name || !fromDate) continue;

    let start = new Date(fromDate);
    let end = toDate ? new Date(toDate) : start; // If no 'To', use 'From'

    if (isNaN(start.getTime())) continue;
    if (isNaN(end.getTime())) end = start;

    // Ensure start <= end
    if (start > end) {
      Logger.log(`Invalid range for ${name}: From ${start} > To ${end}`);
      continue;
    }

    // Loop through all dates in the range and add to map
    let current = new Date(start);
    while (current <= end) {
      const dateStr = current.toDateString();
      if (!leaveMap[dateStr]) leaveMap[dateStr] = new Set();
      leaveMap[dateStr].add(name);

      current.setDate(current.getDate() + 1); // Move to next day
    }
  }

  return leaveMap;
}









function checkDuplicateAssignments() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const data = sheet.getDataRange().getValues();

  const headers = data[0];
  const dateIndex = headers.indexOf("Date");
  const timeIndex = headers.indexOf("Time");
  const assigneeIndex = headers.indexOf("Assignee");

  const lastRow = data.length - 1;
  const lastDate = data[lastRow][dateIndex];
  const lastAssigneesRaw = data[lastRow][assigneeIndex];

  if (!lastDate || !lastAssigneesRaw) {
    SpreadsheetApp.getUi().alert("Last row has no date or assignees.");
    return;
  }

  const lastAssignees = lastAssigneesRaw.split(",").map(p => p.trim());
  const conflicts = [];

  for (let i = 1; i < lastRow; i++) {  // exclude last row itself
    const rowDate = data[i][dateIndex];
    const rowTime = data[i][timeIndex];
    const rowAssigneesRaw = data[i][assigneeIndex];

    if (rowDate && rowAssigneesRaw && rowDate.getTime() === lastDate.getTime()) {
      const rowAssignees = rowAssigneesRaw.split(",").map(p => p.trim());
      for (const person of lastAssignees) {
        if (rowAssignees.includes(person)) {
          conflicts.push(`${person} (previously assigned at ${rowTime})`);
        }
      }
    }
  }

  if (conflicts.length > 0) {
    const message = `Duplicate assignees found for date ${Utilities.formatDate(lastDate, Session.getScriptTimeZone(), "yyyy-MM-dd")}:\n\n` + conflicts.join("\n");
    SpreadsheetApp.getUi().alert(message);
  } else {
    SpreadsheetApp.getUi().alert("No duplicate assignees found for the last entered date.");
  }
}







function showTempDeactivatedUsers() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName("NameLists");
  const ui = SpreadsheetApp.getUi();

  const lastRow = sheet.getLastRow();
  const names = sheet.getRange(2, 2, lastRow - 1).getValues();  // Column B = Name
  const active = sheet.getRange(2, 7, lastRow - 1).getValues(); // Column G
  const tempDeact = sheet.getRange(2, 8, lastRow - 1).getValues(); // Column H

  const tempDeactivatedUsers = [];

  for (let i = 0; i < names.length; i++) {
    const name = names[i][0];
    const isActive = active[i][0];
    const isTempDeact = tempDeact[i][0];

    if (!name) continue;

    if (isActive === false && isTempDeact === true) {
      tempDeactivatedUsers.push(name);
    }
  }

  let message = '⚠️ Temporarily Deactivated Users:\n\n';
  if (tempDeactivatedUsers.length > 0) {
    message += tempDeactivatedUsers.map(name => `• ${name}`).join('\n');
  } else {
    message += '• None';
  }

  ui.alert('Temporary Deactivation Status', message, ui.ButtonSet.OK);
}










function sendDutyInvitation() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var ui = SpreadsheetApp.getUi();

  try {
    var lastRow = sheet.getLastRow();

    var duty = sheet.getRange(lastRow, 2).getValue();      // Column B - Duty
    var dateValue = sheet.getRange(lastRow, 3).getValue(); // Column C - Date
    var timeSlots = sheet.getRange(lastRow, 4).getValue(); // Column D - Time string

    if (!duty || !dateValue || !timeSlots) {
      ui.alert("Please ensure Duty, Date, and Time columns in the last row are filled.");
      return;
    }

    var date = new Date(dateValue);
    if (isNaN(date.getTime())) {
      ui.alert("Date value in the last row is invalid. Please check.");
      return;
    }

    // Parse each slot and convert both start and end to 24-hour immediately
    var slots = timeSlots.split(",").map(function(slot) {
      var parts = slot.trim().split("-");
      if (parts.length !== 2) throw new Error("Time slot format invalid: " + slot);

      var startHour = convertTo24Hour(parseInt(parts[0], 10));
      var endHour = convertTo24Hour(parseInt(parts[1], 10));

      return { start: startHour, end: endHour };
    });

    // Find earliest start and latest end across all slots
    var earliestStart = Math.min(...slots.map(s => s.start));
    var latestEnd = Math.max(...slots.map(s => s.end));

    var startTime = new Date(date);
    startTime.setHours(earliestStart, 0, 0, 0);

    var endTime = new Date(date);
    endTime.setHours(latestEnd, 0, 0, 0);

    if (startTime >= endTime) {
      ui.alert("Start time must be before end time.");
      return;
    }

    // Collect emails from column N starting at row 5
    var emails = [];
    var row = 5;
    while (true) {
      var email = sheet.getRange(row, 14).getValue();
      if (!email) break;
      emails.push(email);
      row++;
    }

    if (emails.length === 0) {
      ui.alert("No emails found in column N starting from N5.");
      return;
    }

    // Format times for confirmation dialog
    var formattedStart = formatTime12(startTime);
    var formattedEnd = formatTime12(endTime);

    var message = 
      "Duty: " + duty + "\n" +
      "Date: " + date.toDateString() + "\n" +
      "Time: " + formattedStart + " - " + formattedEnd + "\n" +
      "Recipients:\n" + emails.join(", ");

    var response = ui.alert("Confirm sending invitation?", message, ui.ButtonSet.OK_CANCEL);

    if (response == ui.Button.OK) {
      var calendar = CalendarApp.getDefaultCalendar();
      calendar.createEvent(
        "[Duty]: " + duty,
        startTime,
        endTime,
        { guests: emails.join(","), sendInvites: true }
      );
      ui.alert("Invitations sent successfully!");
    } else {
      ui.alert("Invitation cancelled.");
    }

  } catch (err) {
    ui.alert("Error: " + err.message);
  }
}

function convertTo24Hour(hour) {
  if (hour === 12) {
    return 12; // 12 noon
  } else if (hour >= 1 && hour <= 7) {
    return hour + 12; // afternoon hours 1pm to 7pm
  } else {
    return hour; // 8am to 11am stays as is
  }
}

function formatTime12(date) {
  var hours = date.getHours();
  var minutes = date.getMinutes();
  var ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12;
  hours = hours ? hours : 12;
  minutes = minutes < 10 ? "0" + minutes : minutes;
  return hours + ":" + minutes + " " + ampm;
}




// function moveTrueRowsToMain() {
//   var ss = SpreadsheetApp.getActiveSpreadsheet();
//   var requestSheet = ss.getSheetByName("Request");
//   var mainSheet = ss.getSheetByName("Main");
  
//   // Get all values from Request
//   var lastRowRequest = requestSheet.getLastRow();
//   var requestData = requestSheet.getRange(2, 1, lastRowRequest - 1, 6).getValues(); // starting from row 2
  
//   // Filter only rows where column F is TRUE
//   var rowsToMove = requestData.filter(function(row) {
//     return row[5] === true; // F column index in 0-based array is 5
//   }).map(function(row) {
//     return row.slice(0, 5); // Take only columns A–E
//   });
  
//   if (rowsToMove.length > 0) {
//     // Find last row in Main
//     var lastRowMain = mainSheet.getLastRow();
    
//     // Append rows
//     mainSheet.getRange(lastRowMain + 1, 1, rowsToMove.length, 5).setValues(rowsToMove);
    
//     // Optional: Reset TRUE to FALSE in Request
//     for (var i = 0; i < requestData.length; i++) {
//       if (requestData[i][5] === true) {
//         requestSheet.getRange(i + 2, 6).setValue(false);
//       }
//     }
//   }
// }



function moveTrueRowsToMain() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var requestSheet = ss.getSheetByName("Request");
  var mainSheet = ss.getSheetByName("Main");
  
  // --- Get all values from Request sheet ---
  var lastRowRequest = requestSheet.getLastRow();
  if (lastRowRequest < 2) return; // no data beyond header
  
  var requestData = requestSheet.getRange(2, 1, lastRowRequest - 1, 6).getValues(); // A–F
  
  // --- Filter rows where column F (index 5) is TRUE ---
  var rowsToMove = requestData.filter(function(row) {
    return row[5] === true;
  }).map(function(row) {
    return row.slice(0, 5); // Only A–E columns to move
  });
  
  if (rowsToMove.length > 0) {
    // --- Find first truly empty row in Main (A–G range) ---
    var mainData = mainSheet.getRange(1, 1, mainSheet.getMaxRows(), 7).getValues(); // A–G
    var emptyRowIndex = mainData.findIndex(function(row, i) {
      // Skip header (assume header in row 1)
      if (i === 0) return false;
      // Row considered empty if all A–G are blank
      return row.every(function(cell) { return cell === "" || cell === null; });
    });
    
    // If no empty row found, append at bottom
    if (emptyRowIndex === -1) {
      emptyRowIndex = mainSheet.getLastRow() + 1;
    } else {
      emptyRowIndex += 1; // because array index starts at 0, but rows start at 1
    }
    
    // --- Insert rows at the first empty location ---
    mainSheet.getRange(emptyRowIndex, 1, rowsToMove.length, 5).setValues(rowsToMove);
    
    // --- Reset TRUE to FALSE in Request ---
    for (var i = 0; i < requestData.length; i++) {
      if (requestData[i][5] === true) {
        requestSheet.getRange(i + 2, 6).setValue(false);
      }
    }
  }
}









function previewEmail() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Main");
  var nameListSheet = ss.getSheetByName("NameLists");

  var lastRow = sheet.getLastRow();
  var coordinatorCode = sheet.getRange(lastRow, 1).getValue(); 
  var courseCode = sheet.getRange(lastRow, 2).getValue();       
  var dateValue = sheet.getRange(lastRow, 3).getValue();        
  var startTimeRaw = sheet.getRange(lastRow, 4).getValue();     
  var formattedDate = Utilities.formatDate(new Date(dateValue), ss.getSpreadsheetTimeZone(), "yyyy-MM-dd");

  // Lookup coordinator name and email
  var nameListData = nameListSheet.getDataRange().getValues();
  var actualCoordinatorName = coordinatorCode; 
  var coordinatorEmail = "";

  for (var i = 0; i < nameListData.length; i++) {
    if (nameListData[i][1] == coordinatorCode) { 
      actualCoordinatorName = nameListData[i][5]; 
      coordinatorEmail = nameListData[i][4];      
      break;
    }
  }

  // --- Format Start Time ---
  var timeParts = startTimeRaw.split(','); 
  var firstHour = parseInt(timeParts[0].split('-')[0]);
  var lastHour = parseInt(timeParts[timeParts.length - 1].split('-')[1]);

  function hourToAmPm(hour) {
    if(hour >= 8 && hour <= 11) return hour + " AM";
    if(hour == 12) return "12 PM";
    if(hour >= 1 && hour <= 7) return hour + " PM";
    return hour;
  }
  var formattedTime = hourToAmPm(firstHour) + " - " + hourToAmPm(lastHour);

  // --- Build table ---
  var dataRange = sheet.getRange(5, 11, sheet.getLastRow() - 4, 4); 
  var tableValues = dataRange.getValues();


  // Build HTML table with padding and aligned left
  var htmlTable = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; margin-left:0; max-width:600px; text-align:center;">';
  htmlTable += '<tr style="background-color:#FFCC99;"><th style="padding:8px;">Code</th><th style="padding:8px;">Name</th><th style="padding:8px;">Number</th><th style="padding:8px;">Email</th></tr>';

  var assignedEmails = [];
  tableValues.forEach(function(row){
    if(row[2]) { 
      htmlTable += '<tr>';
      row.forEach(function(cell){ htmlTable += '<td style="padding:6px;">' + (cell || '') + '</td>'; });
      htmlTable += '</tr>';
      if(row[3]) assignedEmails.push(row[3]);
    }
  });
  htmlTable += '</table>';

  // --- Email body ---
  var emailBody = `
    <div style="background-color:#FFE5B4; padding:20px; font-family:Courier, Arial, sans-serif;">
    <p style="background-color:#FFCC99; padding:10px;" >Dear <strong> ${actualCoordinatorName} </strong>,</p>
    <p>Please find below the details of the assigned supportive member(s) for the upcoming session scheduled as follows:</p>
    <p><strong>Course Code:</strong> ${courseCode}</p>
    <p><strong>Date:</strong> ${formattedDate}</p>
    <p><strong>Time:</strong> ${formattedTime}</p>
    ${htmlTable}
    <br>
    <br>
    <h4 style="background-color:#FFCC99; padding:10px;">Assigned Supportive Member(s)</h4>
    <p><em>Dear assigned member(s),</em></p>
    <p>${assignedEmails.map(e => '@' + e).join(' ')} <em> Kindly meet <span style="background-color:#FFCC99;"><strong> ${actualCoordinatorName} </strong> </span> in advance to clarify your duties. If you are not available for this task, please assign someone else who is available and notify us by replying to this email thread. </em> </p>
    <br>
    Kind regards,
    <p style="color:gray;">Coordination Team<br>
    Amod Pathirana (772836442)<br>
    Lakshani Gayanthika (712020986)
    </p>
    </div>
  `;

  // Store email content in User Properties
  PropertiesService.getUserProperties().setProperty('EMAIL_BODY', emailBody);
  PropertiesService.getUserProperties().setProperty('EMAIL_SUBJECT', "[Duty] - " + courseCode);
  // PropertiesService.getUserProperties().setProperty('EMAIL_RECIPIENT', [coordinatorEmail].concat(assignedEmails).join(','));
  // --- Add two fixed default emails ---
var defaultEmails = ["amd@ucsc.cmb.ac.lk", "lks@ucsc.cmb.ac.lk"];

PropertiesService.getUserProperties().setProperty(
  'EMAIL_RECIPIENT',
  [coordinatorEmail]
    .concat(assignedEmails)
    .concat(defaultEmails)
    .join(',')
);

  // --- Show preview ---
  var htmlOutput = HtmlService.createHtmlOutput(`
    <html>
      <body>
        <h3>Email Preview</h3>
        <div style="border:1px solid #ccc; padding:10px; height:400px; overflow:auto;">${emailBody}</div>
        <br>
        
        <p><strong>Preview Recipient List:</strong> ${[coordinatorEmail].concat(assignedEmails).concat(defaultEmails).join(', ')}</p>
        <button onclick="google.script.run.withSuccessHandler(()=>{google.script.host.close();}).sendEmailConfirmed()">Send Email</button>
        <button onclick="google.script.host.close()">Cancel</button>
      </body>
    </html>
  `).setWidth(700).setHeight(600);

  SpreadsheetApp.getUi().showModalDialog(htmlOutput, 'Preview Email');
}

function sendEmailConfirmed() {
  var emailBody = PropertiesService.getUserProperties().getProperty('EMAIL_BODY');
  var emailSubject = PropertiesService.getUserProperties().getProperty('EMAIL_SUBJECT');
  var recipients = PropertiesService.getUserProperties().getProperty('EMAIL_RECIPIENT');

  MailApp.sendEmail({
    to: recipients,
    subject: emailSubject,
    htmlBody: emailBody
  });

  SpreadsheetApp.getUi().alert("Email sent successfully!");
}

