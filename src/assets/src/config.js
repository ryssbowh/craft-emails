import './common.scss';

Craft.Emails.EmailConfig = Garnish.Base.extend({
    init: function (isAdding) {
        this.initPlainToggle();
        if (isAdding) {
            new Craft.HandleGenerator('#field-heading', '#field-key')
        }
    },

    initPlainToggle: function () {
        $('#lightswitch-plain-field .lightswitch').on('change', () => {
            this.toggleWysiwygField();
        });
        if ($('#lightswitch-plain-field .lightswitch').hasClass('on')) {
            $('#wysiwyg-field').hide();
        }
    },

    toggleWysiwygField: function () {
        if ($('#lightswitch-plain-field .lightswitch').hasClass('on')) {
            $('#wysiwyg-field').slideUp('fast');
        } else {
            $('#wysiwyg-field').slideDown('fast');
        }
    }
})