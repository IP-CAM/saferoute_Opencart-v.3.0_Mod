(function($) {
    var cookie = {
        set: function (name, value, stringifyObject) {
            if (value && stringifyObject) value = encodeURIComponent(JSON.stringify(value));

            var date = new Date();
            date.setTime(date.getTime() + 3 * 24 * 60 * 60 * 1000);

            document.cookie = name + '=' + (value || '')  + '; expires=' + date.toUTCString() + '; path=/';
        },
        remove: function (name) {
            document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        }
    };


    var messages = {
        ru: { cartIsEmpty: 'Корзина пуста' },
        en: { cartIsEmpty: 'Cart is empty' }
    };

    var WIDGET_DOM_ID = 'sr-widget';


    var widget;


    window.SafeRouteWidgetInit = function () {
        var $safeRouteLabel = $('label[for="saferoute.saferoute"]'),
            $shippingRadio = $('input[name=shipping_method]');

        // Если место, где должен отображаться виджет, на странице не существует или в текущий момент
        // не отображается, функцию выполнять не нужно
        if (!$safeRouteLabel.length || $safeRouteLabel.is(':hidden')) return;

        // Удаление из Cookies старых данных при первой загрузке скрипта
        // (при перезапуске виджета очищать Cookies нельзя, т.к. Simple перезагружает блоки на стадии
        // оформления заказа и очистка Cookies помешает сохранить в базе CMS данные виджета и SafeRoute ID заказа)
        if (!widget) {
            cookie.remove('SRWidgetData');
            cookie.remove('SROrderData');
        // Если произошел повторный вызов функции, старый виджет нужно уничтожить
        } else {
            widget.destruct();
        }

        // Элементы поля валидации widget_validation
        var $validationLabel = $('.row-shipping_widget_validation label'),
            $validationInput = $('.row-shipping_widget_validation input');

        // Скрываем поле валидации, оставляя на экране только вывод ошибки
        $validationLabel.remove();
        $validationInput.hide();

        // Если выбрана не доставка SafeRoute, виджет запускать не нужно
        if ($shippingRadio.filter(':checked').val() !== 'saferoute.saferoute')
            return;

        // Если в поле валидации уже есть значение, его нужно удалить
        $validationInput.val('');

        var baseHref = $(document).find('base[href]').attr('href') || '/';

        // Получение настроек сайта / модуля
        $.get(baseHref + 'index.php?route=module/saferoute/get_settings', function (settings) {
            var lang = 'ru';
            switch (settings.lang) {
                case 'en': lang = 'en'; break;
            }

            var currency = 'rub';
            switch (settings.currency) {
                case 'usd': currency = 'usd'; break;
                case 'eur': currency = 'euro'; break;
                case 'euro': currency = 'euro'; break;
            }

            // DOM-узел для монтирования виджета
            $safeRouteLabel.after('<div id="' + WIDGET_DOM_ID + '"></div>');

            // Получение данных корзины (список товаров, габариты)
            $.get(baseHref + 'index.php?route=module/saferoute/get_cart', function (cart) {
                // Корзина пуста, виджет запустить нельзя
                if (!cart.products || !cart.products.length)
                    return alert(messages[lang].cartIsEmpty);

                // Инициализация виджета SafeRoute
                widget = new SafeRouteCartWidget(WIDGET_DOM_ID, {
                    lang: lang,
                    currency: currency,
                    apiScript: baseHref + 'index.php?route=module/saferoute/widget_api',
                    mod: 'opencart_2.x',
                    discount: cart.discount,
                    products: cart.products,
                    weight: cart.weight,

                    // Получение ФИО, телефона и E-mail из соответствующих полей на странице, если вдруг они есть
                    userFullName: $.trim($('input#customer_firstname').val()),
                    userEmail: $.trim($('input#customer_email').val()),
                    userPhone: $.trim($('input#customer_telephone').val()).replace(/[^\d]/g, '')
                });

                // Изменение значений в виджете
                widget.on('change', function (values) {
                    // Сохранение данных виджета в Cookies
                    cookie.set('SRWidgetData', values, true);
                });

                // Заказ передан на сервер SafeRoute
                widget.on('done', function (response) {
                    // Чтобы пройти валидацию поля widget_validation
                    $validationInput.val(1);

                    // Сохранение данных заказа в Cookies
                    cookie.set('SROrderData', {
                        id: response.id,
                        confirmed: response.confirmed
                    }, true);

                    var $buttonBlock = $('.simplecheckout-button-block'),
                        $nextStepBtn = $buttonBlock.find('.button[data-onclick=nextStep]:visible'),
                        $confirmBtn  = $buttonBlock.find('#button-confirm:visible, #simplecheckout_button_confirm:visible');

                    // Блокировка возможности изменения способа доставки (на всякий случай)
                    $shippingRadio.not('[value="saferoute.saferoute"]').prop('disabled', true);

                    // Переход к следующему шагу
                    if ($nextStepBtn.length)
                        $nextStepBtn.trigger('click');
                    // Либо подтверждение заказа
                    else if ($confirmBtn.length)
                        $confirmBtn.trigger('click');
                });

                // Вывод ошибок виджета в консоль
                widget.on('error', function (e) { console.error(e); });
            });
        });
    }
})(jQuery || $);