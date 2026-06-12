(function () {
    if (
        !window.wc ||
        !window.wc.wcBlocksRegistry ||
        !window.wc.wcSettings ||
        !window.wp ||
        !window.wp.element
    ) {
        return;
    }

    var settings = window.wc.wcSettings.getSetting('taramoney_data', {});
    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var createElement = window.wp.element.createElement;
    var decodeEntities = window.wp.htmlEntities && window.wp.htmlEntities.decodeEntities
        ? window.wp.htmlEntities.decodeEntities
        : function (value) {
            return value;
        };

    var title = decodeEntities(settings.title || 'TaraMoney');
    var description = decodeEntities(settings.description || '');

    var Label = function () {
        return createElement(
            'span',
            {
                className: 'olkoo-taramoney-blocks-label',
            },
            settings.icon
                ? createElement('img', {
                    src: settings.icon,
                    alt: '',
                    style: {
                        maxHeight: '24px',
                        maxWidth: '80px',
                        verticalAlign: 'middle',
                        marginRight: '8px',
                    },
                })
                : null,
            title
        );
    };

    var Content = function () {
        return createElement(
            'div',
            {
                className: 'olkoo-taramoney-blocks-content',
            },
            description
        );
    };

    registerPaymentMethod({
        name: 'taramoney',
        paymentMethodId: 'taramoney',
        gatewayId: 'taramoney',
        label: createElement(Label, null),
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: function () {
            return true;
        },
        ariaLabel: title,
        supports: {
            features: settings.supports || ['products'],
        },
    });
}());
