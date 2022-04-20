function handleError(data) {
    if (data.hasOwnProperty('responseJSON')) {
        if (data.responseJSON.hasOwnProperty('message')) {
            Craft.cp.displayError(data.responseJSON.message);
        } else if (data.responseJSON.hasOwnProperty('error')) {
            Craft.cp.displayError(data.responseJSON.error);
        }
    } else if (data.hasOwnProperty('statusText')) {
        Craft.cp.displayError(data.statusText);
    } 
}

function formatEmails(emails) {
    var html = '';
    for (var i in emails) {
        html += '<div><a href=mailto:' + i + '>' + i + (emails[i] ? ' (' + emails[i] + ')' : '') + '</a></div>'
    }
    return html;
}

export { handleError, formatEmails };