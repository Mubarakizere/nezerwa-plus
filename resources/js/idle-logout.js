let idleTime = 0;
const logoutUrl = "/logout";
const idleLimit = 30 * 60 * 1000; // 30 minutes
const warningTime = 25 * 60 * 1000; // 25 minutes

let warningShown = false;
let idleTimer;

function resetTimer() {
  idleTime = 0;
  warningShown = false;
  clearTimeout(idleTimer);
  idleTimer = setTimeout(showWarning, warningTime);
}

function showWarning() {
  warningShown = true;
  alert("⚠️ You have been inactive. You will be logged out in 5 minutes.");
  setTimeout(autoLogout, 5 * 60 * 1000);
}

function autoLogout() {
  fetch(logoutUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
    },
  }).then(() => {
    window.location.href = "/login";
  });
}

// Reset timer on activity
["mousemove", "keypress", "click", "scroll"].forEach(event => {
  document.addEventListener(event, resetTimer);
});

resetTimer();
