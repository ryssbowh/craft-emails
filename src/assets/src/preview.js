Craft.EmailPreview = Garnish.Base.extend({

    emailId: null,
    langId: null,
    isActive: false,
    $editor: null,
    $spinner: null,
    $shade: null,
    $editorContainer: null,
    $previewContainer: null,
    $iframeContainer: null,
    $results: null,
    _editorWidth: null,
    _editorWidthInPx: null,
    defaultEditorWidth: 0.33,
    minEditorWidthInPx: 320,

    init: function(settings) {
        this.langId = settings.langId;
        this.emailId = settings.emailId;
        this.editorWidth = this.defaultEditorWidth;
    },

    get editorWidth() {
        return this._editorWidth;
    },

    get editorWidthInPx() {
        return this._editorWidthInPx;
    },

    set editorWidth(width) {
        var inPx;

        // Is this getting set in pixels?
        if (width >= 1) {
            inPx = width;
            width /= Garnish.$win.width();
        } else {
            inPx = Math.round(width * Garnish.$win.width());
        }

        // Make sure it's no less than the minimum
        if (inPx < this.minEditorWidthInPx) {
            inPx = this.minEditorWidthInPx;
            width = inPx / Garnish.$win.width();
        }

        this._editorWidth = width;
        this._editorWidthInPx = inPx;
    },

    open: function() {
        if (this.isActive) {
            return;
        }

        this.isActive = true;

        $(document.activeElement).trigger('blur');

        if (!this.$editor) {
            this.$shade = $('<div/>', {'class': 'modal-shade dark'}).appendTo(Garnish.$bod);
            this.$previewContainer = $('<div/>', {'class': 'lp-preview-container'}).appendTo(Garnish.$bod);
            this.$editorContainer = $('<div/>', {'class': 'lp-editor-container'}).appendTo(Garnish.$bod);

            var $editorHeader = $('<header/>', {'class': 'flex'}).appendTo(this.$editorContainer);
            this.$editor = $('<form/>', {'class': 'lp-editor'}).appendTo(this.$editorContainer);
            // this.$dragHandle = $('<div/>', {'class': 'lp-draghandle'}).appendTo(this.$editorContainer);
            var $closeBtn = $('<button/>', {
                type: 'button',
                class: 'btn',
                text: Craft.t('app', 'Close Preview'),
            }).appendTo($editorHeader);
            var $refreshBtn = $('<button/>', {
                type: 'button',
                class: 'btn',
                text: Craft.t('app', 'Refresh'),
            }).appendTo($editorHeader);
            $('<div/>', {'class': 'flex-grow'}).appendTo($editorHeader);
            this.$spinner = $('<div/>', {'class': 'spinner hidden', title: Craft.t('app', 'Saving')}).appendTo($editorHeader);

            this.$iframeContainer = $('<div/>', {'class': 'lp-iframe-container'}).appendTo(this.$previewContainer);

            this.addListener($closeBtn, 'click', 'close');
            let _this = this;
            this.addListener($refreshBtn, 'click', function () {
                _this.updateIframe();
            });
        }

        // Set the sizes
        this.updateWidths();
        this.addListener(Garnish.$win, 'resize', 'updateWidths');

        this.$editorContainer.css(Craft.left, -this.editorWidthInPx + 'px');
        this.$previewContainer.css(Craft.right, -this.getIframeWidth());

        $('#content').find('>*').appendTo(this.$editor);

        this.updateIframe();
        this.slideIn();
    },

    close: function() {
        if (!this.isActive || !this.isVisible) {
            return;
        }

        $('html').removeClass('noscroll');

        this.removeListener(Garnish.$win, 'resize');
        this.removeListener(Garnish.$bod, 'keyup');

        this.$editor.find('>*').appendTo($('#content'));

        this.$shade.delay(200).velocity('fadeOut');

        this.$editorContainer.velocity('stop').animateLeft(-this.editorWidthInPx, 'slow', () => {
            this.$editorContainer.hide();
            this.trigger('slideOut');
        });

        this.$previewContainer.velocity('stop').animateRight(-this.getIframeWidth(), 'slow', () => {
            this.$previewContainer.hide();
        });

        this.isActive = false;
        this.isVisible = false;
    },

    updateWidths: function() {
        this.editorWidth = this.editorWidth;
        this.$editorContainer.css('width', this.editorWidthInPx + 'px');
        this.$previewContainer.width(this.getIframeWidth());
    },

    _updateRefreshBtn: function() {
        this.$refreshBtn.removeClass('hidden');
    },

    getIframeWidth: function() {
        return Garnish.$win.width() - this.editorWidthInPx;
    },

    updateIframe: function() {
        if (!this.isActive) {
            return false;
        }

        var url = Craft.getUrl('emails/preview/' + this.emailId + '/' + this.langId);
        var data = {
            subject: $('#field-subject').val(),
            body: $('#field-body-field .redactor-in').html()
        };
        if (!$('#field-body-field .redactor-in').length) {
            data.body = $('#field-body').val();
        }
        this.$iframeContainer.html('');
        axios.post(url, data).then((data) => {
            if (data.data.subjectError) {
                this.$iframeContainer.append('<div class="warning-banner">' + data.data.subjectError);
            }
            if (data.data.bodyError) {
                this.$iframeContainer.append('<div class="warning-banner">' + data.data.bodyError);
            }
            var $results = $('<div id="email-preview">');
            this.$iframeContainer.append($results);

            $results.append('<label>' + Craft.t('emails', 'Subject') +'</label>\
                <div class="subject">\
                    ' + data.data.subject + '\
                </div>'
            );
            $results.append('<label>' + Craft.t('emails', 'Body') +'</label>');
            $iframeWrapper = $('<div class="iframe-wrapper">');
            $results.append($iframeWrapper);
            var $iframe = $('<iframe/>', {
                frameborder: 0
            });
            $iframeWrapper.append($iframe);

            $iframe[0].contentWindow.document.open();
            $iframe[0].contentWindow.document.write(data.data.body);
            $iframe[0].contentWindow.document.close();
        }).catch((error) => {
            let message = error.response.data.error;
            this.$iframeContainer.append('<div class="error-banner">Error while fetching preview : ' + message);
        });
    },

    slideIn: function() {
        if (!this.isActive || this.isVisible) {
            return;
        }

        $('html').addClass('noscroll');
        this.$shade.velocity('fadeIn');

        this.$editorContainer.show().velocity('stop').animateLeft(0, 'slow', () => {
            this.trigger('slideIn');
            Garnish.$win.trigger('resize');
        });

        this.$previewContainer.show().velocity('stop').animateRight(0, 'slow', () => {
            this.addListener(Garnish.$bod, 'keyup', function(ev) {
                if (ev.keyCode === Garnish.ESC_KEY) {
                    this.close();
                }
            });
        });

        this.isVisible = true;
    },
});