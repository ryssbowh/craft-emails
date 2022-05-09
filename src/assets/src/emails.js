import './common.scss';
import './emails.scss';

Craft.Emails.Emails = Garnish.Base.extend({
    init: function () {
        this.initDelete();
    },

    initDelete: function () {
        $('.delete.email').click(function(){
            let line = $(this).parent().parent();
            if (confirm(Craft.t('emails', 'Are you sure you want to delete this email ?'))) {
                $.ajax({
                    url: Craft.getActionUrl('emails/cp-emails/delete'),
                    data: {id: $(this).data('id')},
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data) {
                    if (data.message) {
                        Craft.cp.displayNotice(data.message);
                    }
                    line.remove();
                })
            }
        });
    }
});