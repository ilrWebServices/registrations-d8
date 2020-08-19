/**
 * @file
 * Warn users when navigating away from modified registration forms before
 * submitting them.
 */
((document, window) => {

  let reg_forms = document.querySelectorAll('form.registration-simple-class-embedded-form');

  reg_forms.forEach(reg_form => {
    reg_form.addEventListener('change', event => {
      reg_form.setAttribute('data-erf-form-changed', '1');
    });

    reg_form.addEventListener('submit', event => {
      reg_form.setAttribute('data-erf-form-changed', '0');
    });
  });

  window.addEventListener('beforeunload', event => {
    let changed_form_count = 0;

    reg_forms.forEach(reg_form => {
      if (reg_form.dataset.erfFormChanged === '1') {
        changed_form_count++;
      }
    });

    if (changed_form_count > 0) {
      // Note that this string is ignored by most modern browsers. See
      // https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event
      event.returnValue = 'There are changes to this form. Sure you want to leave?';
    }
  });

})(document, window);
