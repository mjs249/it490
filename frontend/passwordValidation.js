function validateForm() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;
    var message = document.getElementById("message");

    if (password != confirmPassword) {
        message.textContent = "Passwords do not match.";
        message.style.color = "red";
        return false;
    } else {
        // If additional validation passes, you can add it here
        message.textContent = "";
        return true;
    }
}

document.getElementById("password").onkeyup = validateForm;
document.getElementById("confirm_password").onkeyup = validateForm;
