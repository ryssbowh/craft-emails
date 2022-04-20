import { handleError, formatEmails } from './helpers';
import './common.scss';
import './shots.scss';

Craft.EmailShots = Garnish.Base.extend({
    modal: null,

    init: function () {
        this.initAddEmail();
        this.initModal();
        this.initViewEmails();
        this.initDelete();
        this.initSend();
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
                    $('#emails-modal .content').html(formatEmails(data.emails));
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
                    handleError(data);
                }).done(function (data){
                    if (data.message) {
                        Craft.cp.displayNotice(data.message);
                    }
                    button.parent().parent().parent().remove();
                    if ($('#shots-table-view tbody tr').length == 0) {
                        $('#shots-table-view').remove();
                        $('#content').append('<p>' + Craft.t('emails', "No email shots found") + '</p>');
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
                    handleError(data);
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