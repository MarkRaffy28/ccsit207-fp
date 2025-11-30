const forms = document.querySelectorAll('form:not(#otp_form)');
forms.forEach(form => {
  form.addEventListener("submit", event => {
    if (event.submitter && event.submitter.hasAttribute("formnovalidate")) {
      return;
    }
    
    if (!form.checkValidity()) {
      event.preventDefault();
    }
    
    form.classList.add("was-validated");
  });
});

const toggleButtons = document.querySelectorAll('.eye');

toggleButtons.forEach(button => {
  button.addEventListener('click', () => {
    const parent = button.closest('.form-floating');
    const input = parent.querySelector('input[type="password"], input[type="text"]');

    input.type = input.type === 'password' ? 'text' : 'password';

    button.classList.toggle('bi-eye');
    button.classList.toggle('bi-eye-slash');
  });
});

const requiredFields = document.querySelectorAll('input[required], textarea[required], select[required]');

requiredFields.forEach(field => {
  const parent = field.parentElement;
  const label = parent.querySelector('label');

  const asterisk = document.createElement('span');
  asterisk.textContent = ' *';
  asterisk.style.color = 'red';
  asterisk.classList.add('required-asterisk');
  label.appendChild(asterisk);
});


function runFinesCalculator() {
  fetch("fines_calculator.php")
  .then(data => console.log("Fines updated:", data))
  .catch(err => console.error("Error:", err));
}

runFinesCalculator();
setInterval(runFinesCalculator, 5 * 60 * 1000);

