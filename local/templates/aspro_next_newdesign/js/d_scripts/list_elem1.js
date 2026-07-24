     if (typeof window['trackBarOptions'] !== 'undefined') {
                window['trackBarValues'] = {}
                for (key in window['trackBarOptions']) {
                    window['trackBarValues'][key] = {
                        'leftPercent': window['trackBar' + key].leftPercent,
                        'leftValue': window['trackBar' + key].minInput.value,
                        'rightPercent': window['trackBar' + key].rightPercent,
                        'rightValue': window['trackBar' + key].maxInput.value,
                    }
                }
            }

            if ($('.filter_wrapper_ajax').length)
                $('.filter_wrapper_ajax').remove();
            var filter_node = $('.left_block .bx_filter.bx_filter_vertical'),
                new_filter_node = $('<div class="filter_wrapper_ajax"></div>'),
                left_block_node = $('#content .left_block');
            if (!filter_node.length) {
                if (left_block_node.find('.menu_top_block').length)
                    new_filter_node.insertAfter(left_block_node.find('.menu_top_block'));
            } else {
                new_filter_node.insertBefore(filter_node);
                filter_node.remove();
            }
            $('.filter_tmp').appendTo($('.filter_wrapper_ajax'));

            if (typeof window['trackBarOptions'] !== 'undefined') {
                for (key in window['trackBarOptions']) {
                    window['trackBarOptions'][key].leftPercent = window['trackBarValues'][key].leftPercent;
                    window['trackBarOptions'][key].rightPercent = window['trackBarValues'][key].rightPercent;
                    window['trackBarOptions'][key].curMinPrice = window['trackBarValues'][key].leftValue;
                    window['trackBarOptions'][key].curMaxPrice = window['trackBarValues'][key].rightValue;
                    window['trackBar' + key] = new BX.Iblock.SmartFilter(window['trackBarOptions'][key]);
                    window['trackBar' + key].minInput.value = window['trackBarValues'][key].leftValue;
                    window['trackBar' + key].maxInput.value = window['trackBarValues'][key].rightValue;
                }
            }