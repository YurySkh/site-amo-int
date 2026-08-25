"use strict";

const form = document.querySelector("#lead-form");
const phoneInput = document.querySelector("#phone");
const phoneCounter = document.querySelector("#phone-counter");
const phoneError = document.querySelector("#phone-error");
const priceInput = document.querySelector("#price");

const keepDigitsOnly = (input) => {
    const digits = input.value.replace(/\D/g, "");

    if (input.value !== digits) {
        input.value = digits;
    }
};

const setPhoneError = (message) => {
    phoneError.textContent = message;

    if (message) {
        phoneInput.setAttribute("aria-invalid", "true");
        return;
    }

    phoneInput.removeAttribute("aria-invalid");
};

const validatePhone = () => {
    const phoneLength = phoneInput.value.length;

    if (phoneLength === 0) {
        setPhoneError("Поле Телефон обязательное для заполнения");
        return false;
    }

    if (phoneLength < 5) {
        setPhoneError("Введите не менее 5 цифр.");
        return false;
    }

    setPhoneError("");
    return true;
};

phoneInput.addEventListener("input", () => {
    keepDigitsOnly(phoneInput);
    phoneCounter.textContent = `${phoneInput.value.length} / 15`;

    if (phoneInput.hasAttribute("aria-invalid")) {
        validatePhone();
    }
});

phoneInput.addEventListener("blur", validatePhone);

priceInput.addEventListener("input", () => {
    keepDigitsOnly(priceInput);
});

form.addEventListener("submit", (event) => {
    if (validatePhone()) {
        return;
    }

    event.preventDefault();
    phoneInput.focus();
});
