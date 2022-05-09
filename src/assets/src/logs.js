import './common.scss';
import './logs.scss';

Craft.Emails.EmailLogs = Garnish.Base.extend({
    selection: [],
    deleteAction: null,
    viewAction: null,
    modal: null,

    init: function (settings) {
        this.deleteAction = settings.deleteAction;
        this.viewAction = settings.viewAction;
        this.initSelect();
        this.initSelectAll();
        this.initDeleteAll();
        this.initDeleteSelection();
        this.initView();
        this.initModal();
        this.initResend();
    },

    initDeleteAll: function () {
        $('.js-delete-all').click(e => {
            e.preventDefault();
            var id = $(e.target).data('id');
            if (confirm(Craft.t('emails', 'Are you sure you want to delete all logs?'))) {
                $.ajax({
                    url: Craft.getActionUrl(this.deleteAction),
                    data: {id: id},
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data){
                    location.reload();
                });
            }
        });
    },

    initDeleteSelection: function () {
        $('.js-delete-selection').click(e => {
            e.preventDefault();
            var id = $(e.target).data('id');
            if (confirm(Craft.t('emails', 'Are you sure you want to delete these logs?'))) {
                $.ajax({
                    url: Craft.getActionUrl(this.deleteAction),
                    data: {
                        id: id,
                        ids: this.selection
                    },
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data){
                    location.reload();
                });
            }
        });
    },

    initSelectAll: function () {
        $('.js-select-all').click((e) => {
            var checked = $(e.target).is(':checked');
            $.each($('.js-select'), (i, item) => {
                $(item).prop('checked', checked);
                if (checked) {
                    this.select($(item).data('id'));
                } else {
                    this.unselect($(item).data('id'));
                }
            });
        });
    },

    initView: function () {
        $('.js-view').click(e => {
            e.preventDefault();
            $.ajax({
                url: Craft.getActionUrl(this.viewAction),
                data: {id: $(e.target).data('id')}
            }).done((data) => {
                var iframe = $('#emails-modal iframe')[0];
                iframe.src = "data:text/html;charset=utf-8," + data.body;
                $('#emails-modal .email-subject').html(data.subject);
                $('#emails-modal .email-to').html(Craft.Emails.formatEmails(data.to));
                $('#emails-modal .email-cc').html(Craft.Emails.formatEmails(data.cc));
                $('#emails-modal .email-bcc').html(Craft.Emails.formatEmails(data.bcc));
                $('#emails-modal .email-from').html(Craft.Emails.formatEmails(data.from));
                $('#emails-modal .email-replyto').html(Craft.Emails.formatEmails(data.replyTo));
                var html = '';
                for (var i in data.attachements) {
                    html += '<div><a target="_blank" href="' + data.attachements[i].url + '">' + data.attachements[i].title + '</a></div>';
                }
                $('#emails-modal .email-attachements').html(html);
                this.modal.show();
            }).fail(data => {
                Craft.Emails.handleError(data);
            });
        });
    },

    initSelect: function () {
        $('.js-select').click(e => {
            if ($(e.target).is(':checked')) {
                this.select($(e.target).data('id'));
            } else {
                this.unselect($(e.target).data('id'));
            }
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

    initResend: function () {
        $('.js-resend').click(function (e) {
            e.preventDefault();
            let id = $(this).data('id');
            let button = $(this);
            if (confirm(Craft.t('emails', "Are you sure you want to resend this email now?"))) {
                $(this).find('.spinner').show();
                $(this).attr('disabled', true);
                $.ajax({
                    url: Craft.getActionUrl('emails/cp-emails/resend'),
                    data: {id: id},
                    dataType: 'json'
                }).fail(function (data) {
                    Craft.Emails.handleError(data);
                }).done(function (data){
                    if (data.message) {
                        Craft.cp.displayNotice(data.message);
                    }
                }).always(function() {
                    button.find('.spinner').hide();
                    button.attr('disabled', false);
                });
            }
        });
    },

    toggleDeleteButton: function () {
        if (!this.selection.length) {
            $('.js-delete-selection').hide();
        } else {
            $('.js-delete-selection').show();
        }
    },

    select: function (id) {
        if (!this.selection.includes(id)) {
            this.selection.push(id);
        }
        this.toggleDeleteButton();
    },

    unselect: function (id) {
        this.selection = this.selection.filter(value => {
            return value != id;
        });
        this.toggleDeleteButton();
        $('.js-select-all').prop('checked', false);
    }
});

Craft.Emails.ShotLogs = Craft.Emails.EmailLogs.extend({
    initView: function () {
        $('.js-view').click(e => {
            e.preventDefault();
            $.ajax({
                url: Craft.getActionUrl('emails/cp-shots/log-emails'),
                data: {id: $(e.target).data('id')}
            }).fail(function (data) {
                Craft.Emails.handleError(data);
            }).done(data => {
                if (data.emails.length == 0) {
                    $('#emails-modal .content').html('<p>' + Craft.t('emails', "No emails for this log") + '</p>');
                } else {
                    $('#emails-modal .content').html(Craft.Emails.formatEmails(data.emails));
                }
                this.modal.show();
            })
        });
    },
});