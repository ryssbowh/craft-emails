Craft.Emails = {
    handleError: function (data) {
        if (data.hasOwnProperty('responseJSON')) {
            if (data.responseJSON.hasOwnProperty('message')) {
                Craft.cp.displayError(data.responseJSON.message);
            } else if (data.responseJSON.hasOwnProperty('error')) {
                Craft.cp.displayError(data.responseJSON.error);
            }
        } else if (data.hasOwnProperty('statusText')) {
            Craft.cp.displayError(data.statusText);
        } 
    },

    formatEmails: function (emails) {
        var html = '';
        for (var i in emails) {
            html += '<div><a href=mailto:' + i + '>' + i + (emails[i] ? ' (' + emails[i] + ')' : '') + '</a></div>'
        }
        return html;
    },

    EmailsField: Garnish.Base.extend({
        $container: null,

        init: function (id) {
            this.$container = $('#' + id);
            this.initAddEmail();
            this.initElem(this.$container.find('.email-element'));
        },

        initAddEmail: function () {
            this.$container.find('.js-add-email').click(() => {
                this.addEmail();
            });
        },

        addEmail: function () {
            let index = this.$container.find('.elements .email-element').length;
            let elem = this.$container.find('.skeleton').clone().removeClass('skeleton');
            let nameAttr = elem.find('.name-input').attr('name');
            let emailAttr = elem.find('.email-input').attr('name');
            elem.find('.name-input').attr('disabled', false).attr('name', nameAttr + '[' + index + '][name]');
            elem.find('.email-input').attr('disabled', false).attr('name', emailAttr + '[' + index + '][email]');
            this.$container.find('.elements').append(elem);
            elem.slideDown('fast');
            elem.find('input').first().focus();
            this.initElem(elem);
        },

        initElem: function (elem) {
            elem.find('.delete').on('click', (e) => {
                $(e.target).parent().slideUp('fast', () => {
                    $(e.target).parent().remove();
                    this.rebuildNames();
                });
            });
        },

        rebuildNames: function () {
            let attr, match, index, matches, newAttr;
            $.each(this.$container.find('.elements .email-element'), (i, item) => {
                attr = $(item).find('.name-input').attr('name');
                matches = [...attr.matchAll(/\[[0-9]+\]/g)];
                match = matches[matches.length - 1][0];
                index = attr.lastIndexOf(match);
                newAttr = attr.substring(0, index) + '[' + i + ']' + attr.substring(index + match.length);
                $(item).find('.name-input').attr('name', newAttr);
                attr = $(item).find('.email-input').attr('name');
                matches = [...attr.matchAll(/\[[0-9]+\]/g)];
                match = matches[matches.length - 1][0];
                index = attr.lastIndexOf(match);
                newAttr = attr.substring(0, index) + '[' + i + ']' + attr.substring(index + match.length);
                $(item).find('.email-input').attr('name', newAttr);
            });
        }
    })
};