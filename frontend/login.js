function checkCapsLock(e) {
    var capsLockWarning = document.getElementById('capsLockWarning');
    var keyCode = e.keyCode ? e.keyCode : e.which;
    var shiftKey = e.shiftKey ? e.shiftKey : ((keyCode == 16) ? true : false);
    if (((keyCode >= 65 && keyCode <= 90) && !shiftKey) || ((keyCode >= 97 && keyCode <= 122) && shiftKey)) {
        capsLockWarning.style.display = 'block';
    } else {
        capsLockWarning.style.display = 'none';
    }
}
function togglePasswordVisibility() {
    const passwordInput = document.getElementById("password");
    const toggleButton = document.getElementById("togglePassword").querySelector('i'); 

    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    toggleButton.classList.toggle("fa-eye");
    toggleButton.classList.toggle("fa-eye-slash");
}


function showCapsLockWarning(e) {
    const capsLockWarning = document.getElementById("capsLockWarning");
    const isCapsLockOn = e.getModifierState("CapsLock");

    capsLockWarning.style.display = isCapsLockOn ? "block" : "none";
}

function validateLoginForm() {
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    
    if (usernameInput.value.trim() === "" || passwordInput.value.trim() === "") {
        alert("Both username and password are required.");
        return false; 
    }
    
    return true; 
}
document.addEventListener("DOMContentLoaded", function() {
    const passwordInput = document.getElementById("password");
    const toggleButton = document.getElementById("togglePassword");

    toggleButton.addEventListener("click", togglePasswordVisibility);

    // Caps lock warning
    passwordInput.addEventListener("keyup", showCapsLockWarning);

    const loginForm = document.querySelector("form");
    loginForm.addEventListener("submit", function(event) {
        if (!validateLoginForm()) {
            event.preventDefault(); 
        }        
    });
});

document.getElementById('password').addEventListener('keyup', function(e) {
    var capsWarning = document.getElementById('capsLockWarning');
    var isCapsOn = e.getModifierState('CapsLock');

    if (isCapsOn) {
        capsWarning.style.display = 'block';
    } else {
        capsWarning.style.display = 'none';
    }
});
