export function saveCookie(firstName, lastName, userId, role = 'User') {
  let minutes = 20;
  let date = new Date();
  date.setTime(date.getTime() + minutes * 60 * 1000);
  document.cookie =
    "firstName=" +
    encodeURIComponent(firstName) +
    ",lastName=" +
    encodeURIComponent(lastName) +
    ",userId=" +
     userId +
     ",role=" +
     role +
     ";expires=" +
     date.toGMTString() +
     ";path=/";
}

export function clearCookie() {
  document.cookie.split(";").forEach(function (cookie) {
    const name = cookie.split("=")[0].trim();

    if (name) {
      document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
    }
  });
}

export function readCookie() {
  let userId = -1;
  let user = {}
  let data = document.cookie;
  let splits = data.split(";");
  for (var i = 0; i < splits.length; i++) {
    let pair = splits[i].trim();
    let tokens = pair.split(",");
    for (var j = 0; j < tokens.length; j++) {
      let keyVal = tokens[j].trim().split("=");
      if (keyVal[0] === "firstName") {
        user.firstName = decodeURIComponent(keyVal[1] || "");
      } else if (keyVal[0] === "lastName") {
        user.lastName = decodeURIComponent(keyVal[1] || "");
      } else if (keyVal[0] === "userId") {
        userId = parseInt(keyVal[1].trim());
        user.userId = userId
      } 
      else if(keyVal[0] == 'role'){
        user.role = decodeURIComponent(keyVal[1] || "");
      }
    }
  }

  if (userId < 0 || isNaN(userId)) {
    window.location.href = "index.html";
  }

  return user
}
