function checkPasswordsMatch() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;
    var message = document.getElementById("message");

    if (password !== confirmPassword) {
        message.textContent = "Passwords do not match.";
        message.style.color = "red";
    } else {
        message.textContent = "Passwords match.";
        message.style.color = "green";
    }
}

function togglePasswordVisibility() {
    var passwordInput = document.getElementById("password");
    var toggleButtonIcon = document.getElementById("togglePassword").querySelector('e');

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButtonIcon.classList.remove("fa-eye");
        toggleButtonIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        toggleButtonIcon.classList.remove("fa-eye-slash");
        toggleButtonIcon.classList.add("fa-eye");
    }
}

function showCapsLockWarning(e) {
    var capsLockWarning = document.getElementById('capsLockWarning');
    capsLockWarning.style.display = e.getModifierState('CapsLock') ? 'block' : 'none';
}

function validateForm() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    return true; 
}


document.getElementById("confirm_password").addEventListener("input", checkPasswordsMatch);
document.getElementById("togglePassword").addEventListener("click", togglePasswordVisibility);
document.getElementById("password").addEventListener("keyup", showCapsLockWarning);
document.getElementById("confirm_password").addEventListener("keyup", showCapsLockWarning);


