BX.ready(function () {
  BX.Aspro.Loader.addExt([
    'validate'
  ]).then(() => {
    $('form.subscribe-form').validate({
      submitHandler: function (form) {
        if ($(form).valid()) {
          BX.onCustomEvent('onSubmitForm', [{
              type: 'form_submit',
              form: form,
              form_name: 'subscribe-footer',
          }]);
        }
      },
      rules: {
        EMAIL: {
          email: true,
        }
      },
    });
  });
});
