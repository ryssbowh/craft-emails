import { handleError, formatEmails } from './helpers';
import './common.scss';
import './shots.scss';

Craft.EmailEditShot = Garnish.Base.extend({
    init: function (settings) {
        this.initAddEmail();
        new Craft.BaseElementSelectInput(settings.jsSettings);
        if (settings.isAdding) {
            new Craft.HandleGenerator('#field-name', '#field-handle')
        }
    },

    initAddEmail: function () {
        $('.js-add-email').click(function () {
            var elem = $('<div class="email-element flex flex-nowrap"><input name="emails[]" type="email" class="text" placeholder="' + Craft.t('emails', "Email") + '"><input name="names[]" type="text" class="text" placeholder="' + Craft.t('emails', "Name") + '"><button type="button" class="delete icon" title="' + Craft.t('emails', "Remove") + '" aria-label="' + Craft.t('emails', "Remove") + '"></button></div>');
            elem.hide();
            $('#field-emails .elements').append(elem);
            elem.slideDown('fast');
            elem.find('input').first().focus();
        });

        $(document).on('click', '.email-element .delete', function(e){
            $(this).parent().slideUp('fast', function () {
                $(this).remove();
            });
        });
    }
});