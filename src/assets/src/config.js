import './common.scss';

Craft.EmailConfig = Garnish.Base.extend({
    init: function (isAdding) {
        this.initPlainToggle();
        if (isAdding) {
            new Craft.HandleGenerator('#field-heading', '#field-key')
        }
    },

    initPlainToggle: function () {
        $('#lightswitch-plain-field .lightswitch').on('change', () => {
            this.toggleRedactorField();
        });
        if ($('#lightswitch-plain-field .lightswitch').hasClass('on')) {
            $('#redactor-config-field').hide();
        }
    },

    toggleRedactorField: function () {
        if ($('#lightswitch-plain-field .lightswitch').hasClass('on')) {
            $('#redactor-config-field').slideUp('fast');
        } else {
            $('#redactor-config-field').slideDown('fast');
        }
    }
})