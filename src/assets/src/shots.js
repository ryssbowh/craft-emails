import './common.scss';
import './shots.scss';

Craft.Emails.Shots = Garnish.Base.extend({
    modal: null,

    init: function () {
        this.initModal();
        this.initViewEmails();
        this.initDelete();
        this.initSend();
    },

    initModal: function () {
        this.modal = new Garnish.Modal($('#emails-modal'), {
            autoShow: false
        });

        $('#emails-modal .js-close').click(() => {
            this.modal.hide();
        });
    },

    initViewEmails: function () {
        $('.js-view').click(e => {
            e.preventDefault();
            $.ajax({
                url: Craft.getActionUrl('emails/cp-shots/shot-emails'),
                data: {id: $(e.target).data('id')}
            }).done(data => {
                if (data.emails.length == 0) {
                    $('#emails-modal .content').html('<p>' + Craft.t('emails', "No emails for this shot") + '</p>');
                } else {
                    $('#emails-modal .content').html(Craft.Emails.formatEmails(data.emails));
                }
                this.modal.show();
            })
        });
    },

    initDelete: function () {
        $('.js-delete').click(e => {
            e.preventDefault();
            let id = $(e.target).data('id');
            let button = $(e.target);
            if (confirm(Craft.t('emails', "Are you sure you want to delete this email shot ?"))) {
                $.ajax({
                    url: Craft.getActionUrl('emails/cp-shots/delete'),
                    data: {id: id},
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data){
                    if (data.message) {
                        Craft.cp.displayNotice(data.message);
                    }
                    button.closest('tr').remove();
                    if ($('#shots-table-view tbody tr').length == 0) {
                        $('#shots-table-view').remove();
                        $('#content .no-shots').removeClass('hidden');
                    }
                });
            }
        });
    },

    initSend: function () {
        $('.js-send').click(function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let button = $(this);
            if (confirm(Craft.t('emails', "Are you sure you want to send this email shot now?"))) {
                $(this).find('.spinner').show();
                $(this).attr('disabled', true);
                $.ajax({
                    url: Craft.getActionUrl('emails/cp-shots/send'),
                    data: {id: id},
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data){
                    if (data.message) {
                        Craft.cp.displayNotice(data.message);
                    }
                    if (data.error) {
                        Craft.cp.displayError(data.error);
                    }
                }).always(function() {
                    button.find('.spinner').hide();
                    button.attr('disabled', false);
                });
            }
        });
    }
});