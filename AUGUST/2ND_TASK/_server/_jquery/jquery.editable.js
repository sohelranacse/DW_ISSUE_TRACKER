(function($) {
	$.fn.editable = function (options) {

	   return this.each(function() {
			options = $.extend({
					lAdd: "Click to add...",
					lEdit: "Click to edit...",
					lSave: "Save...",
					inputLength: 50,
					type: "album",
					typeData: "desc", //"title"
					style: '',
					classHover: 'input_editable_hover',
					colorHover: '',
					location: 'ajax.php',
					empty: false,
                    setContentEditable: true,
                    hBlur: false,
                    hBeforeSend: false,
                    hSuccessSend: false,
			}, options);

			var container = $(this).attr({spellcheck:false}).addClass('editable');
            if (options.setContentEditable) {
                container.attr({contenteditable:true});
            }
			container.data('startEdit', false);
            var text=$.trim(container.text());
            if(text==options.lAdd)text='';
			container.data('valueDefault', text);
			if (text.replace(/\s/g,'') == '') {
				container.text(options.lAdd);
			}
            container.data('colorHoverDefault', container.css('background-color'));
			container.data('classHoverDefault', container.attr('class'));

			$(window).load(function(){
				container.css({minHeight: container.height(), minWidth: container.width()})
			});
			container.focus(function(){
                container.css({minHeight: container.height(), minWidth: container.width()});
                if (container.text()==options.lAdd) container.text('');
			}).on('mouseover', function() {
				if (options.colorHover != '')
					container.css('background-color', options.colorHover);
				(options.classHover!=='')&&container.attr('class', container.data('classHoverDefault') + ' ' + options.classHover);
			}).on('mouseout', function() {
				if (options.colorHover != '')
					container.css('background-color', container.data('colorHoverDefault'));
				(options.classHover!=='')&&container.attr('class', container.data('classHoverDefault'));
			}).on('keydown paste drop', function(e){
                container.css({minHeight: '', minWidth: ''});
				if(e.keyCode == 13) {
                    container.blur();
                    return false;
                }
				var val=container.text();
				setTimeout(function(){
					if (e.type!='keydown') val=container.text();
					if (container.text().length>options.inputLength || e.type!='keydown')
						container.text(val.substr(0, options.inputLength))
				}, 1);
			}).on('blur', function(){
				container.css({minHeight: '', minWidth: ''})
				var value=$.trim(container.text()).substr(0, options.inputLength),
					id=container[0].id.match(/[^_]+$/)[0],
					changed=(value!=container.data('valueDefault'));
				if ((value!='' || options.empty) && changed){
					if(id == 'status' && $("#ua_user_id").val()) // added by sohel
						id = $("#ua_user_id").val()

					$.ajax({type: 'POST',
							url: options.location,
							data: {'cmd':'saveoptions',
								   'data': value,
								   'type': options.type,
								   'type_data': options.typeData,
								   'id': id},
							beforeSend: function(){
                                options.lSave&&container.text(options.lSave);
                                if (typeof options.hBeforeSend == 'function') {
                                    options.hBeforeSend();
                                }
							},
							dataFilter: function(){
								container.data('valueDefault', value);
							},
							success: function(){
                                if (typeof options.hSuccessSend == 'function') {
                                    options.hSuccessSend();
                                }
								var valueDefault = container.data('valueDefault');
								if (!value && !valueDefault)
									container.text(options.lAdd).attr('title', options.lAdd)
								else
									container.text(valueDefault).attr('title', valueDefault);
								container.data('startEdit', false)
								 .css({minHeight: container.height(), minWidth: container.width()})
							}
					});
				} else {
					var valueDefault = container.data('valueDefault');
					if (valueDefault == '')
						container.text(options.lAdd)
					else
						container.text(valueDefault);
					container.data('startEdit', false)
					 .css({minHeight: container.height(), minWidth: container.width()})
				}
                if (typeof options.hBlur == 'function') {
                    options.hBlur();
                }
			}).on('dragenter', function(e){this.focus()});
		});
	};
})(jQuery)

