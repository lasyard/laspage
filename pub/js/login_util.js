function encrypt(form) {
    form.password.value = CryptoJS.SHA256(form.password.value);
    return true;
}
