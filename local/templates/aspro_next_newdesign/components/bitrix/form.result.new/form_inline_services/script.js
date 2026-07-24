function ajaxForm(obForm, link) {
    BX.bind(obForm, 'submit', BX.proxy(function(e) {
        BX.PreventDefault(e);
        obForm.getElementsByClassName('error-msg')[0].innerHTML = '';
 ym(62259859,'reachGoal','SUBMITRUFORM');
        let xhr = new XMLHttpRequest();
        xhr.open('POST', link);
 
        xhr.onload = function() {
            if (xhr.status != 200) {
                alert(`Ошибка ${xhr.status}: ${xhr.statusText}`);
            } else {
                var json = JSON.parse(xhr.responseText)
 
                if (! json.success) {
                    let errorStr = '';
                    for (let fieldKey in json.errors) {
                        errorStr += json.errors[fieldKey] + '<br>';
                    }
                     
                    // Ошибки вывести в элемент с классом error-msg
                    obForm.getElementsByClassName('error-msg')[0].innerHTML = errorStr;
                } else {
//alert('Ваше сообщение успешно отправлено');
$.fancybox.open({
		src: '#hidden555',
		type: 'inline'
	});
 //setTimeout($.fancybox.close(), 1000);
 setTimeout("$.fancybox.close()", 2000);
obForm.reset();
                    // Показываем сообщение об успешной отправке
                    // popupSuccess()
                }
            }
        };

        xhr.onerror = function() {
            alert("Запрос не удался");
        };
 
        // Передаем все данные из формы
        xhr.send(new FormData(obForm));
    }, obForm, link));
}