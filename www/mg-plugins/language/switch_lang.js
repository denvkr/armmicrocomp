function switch_lang(lang) {


  	   if (lang=='EN') {
		var tbl_element1 = 'Microcomputers<br />Moscow city, Dovgenko str., 8/1<br />Phone: +7 (916) 141-21-80<br />Working hours from 9:00 till 21:00<br /> mail: admin@armmicrocomp.com<br />ICQ 344-360-162';
	   } else if (lang=='RU') {
		var tbl_element1 = 'Микрокомпьютеры<br />г. Москва, ул. Довженко, д. 8/1<br />Телефон: +7 (916) 141-21-80<br />Мы работаем ежедневно с 9:00 до 21:00<br /> почта: admin@armmicrocomp.com<br />ICQ 344-360-162';
		
}
$('#vcard_info_td').html(tbl_element1);

}

function showborder(element){ 
       var elm = document.getElementById(element);
       elm.style.border="0.5px solid #CCCCCC";
       console.log(elm.style.border);
}

function hideborder(element){ 
       var elm = document.getElementById(element);
       elm.style.border="0.5px";
       console.log(elm.style.border);
}
