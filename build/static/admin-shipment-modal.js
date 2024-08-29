window.germanized=window.germanized||{},window.germanized.admin=window.germanized.admin||{},function(a,o,n,t){var i=function(e){var o=this;o.params=wc_gzd_admin_shipment_modal_params,o.$modalTrigger=e,o.destroy(),o.setup(),o.$modalTrigger.on("click.gzd-modal-"+o.modalId,{adminShipmentModal:o},o.onClick),a(n.body).on("wc_backbone_modal_loaded.gzd-modal-"+o.modalId,{adminShipmentModal:o},o.onOpen).on("wc_backbone_modal_response.gzd-modal-"+o.modalId,{adminShipmentModal:o},o.response).on("wc_backbone_modal_before_remove.gzd-modal-"+o.modalId,{adminShipmentModal:o},o.onClose)};i.prototype.setup=function(){var e=this;e.referenceId=e.$modalTrigger.data("reference")?e.$modalTrigger.data("reference"):0,e.modalClass=e.$modalTrigger.data("id"),e.modalId=e.modalClass+"-"+e.referenceId,e.loadAsync=!!e.$modalTrigger.data("load-async")&&e.$modalTrigger.data("load-async"),e.nonceParams=e.$modalTrigger.data("nonce-params")?e.$modalTrigger.data("nonce-params"):"wc_gzd_admin_shipments_params",e.$modal=!1,e.$modalTrigger.data("self",this)},i.prototype.destroy=function(){var e=this;e.$modalTrigger.off(".gzd-modal-"+e.modalId),a(n).off(".gzd-modal-"+e.modalId),a(n.body).off(".gzd-modal-"+e.modalId)},i.prototype.getShipment=function(e){return a("#panel-order-shipments").find("#shipment-"+e)},i.prototype.onRemoveNotice=function(e){return e.data.adminShipmentModal,a(this).parents(".notice").slideUp(150,(function(){a(this).remove()})),!1},i.prototype.onClick=function(e){var a=e.data.adminShipmentModal;return a.$modalTrigger.WCBackboneModal({template:a.modalId}),!1},i.prototype.parseFieldId=function(e){return e.replace("[","_").replace("]","")},i.prototype.onExpandMore=function(e){var o=e.data.adminShipmentModal,n=o.$modal.find(".show-more-wrapper"),t=a(this).parents(".show-more-trigger");return n.show(),n.find(":input:visible").trigger("change",[o]),t.find(".show-more").hide(),t.find(".show-fewer").show(),!1},i.prototype.onHideMore=function(e){var o=e.data.adminShipmentModal.$modal.find(".show-more-wrapper"),n=a(this).parents(".show-more-trigger");return o.hide(),n.find(".show-further-services").show(),n.find(".show-fewer-services").hide(),!1},i.prototype.onChangeField=function(e){var o=e.data.adminShipmentModal,t=o.$modal,i=o.parseFieldId(a(this).attr("id")),d=a(this).val();if(a(this).attr("max")){var r=a(this).attr("max");d>r&&a(this).val(r)}if(a(this).attr("min")){var s=a(this).attr("min");d<s&&a(this).val(s)}if(a(this).hasClass("show-if-trigger")){var m=t.find(a(this).data("show-if"));m.length>0&&(a(this).is(":checked")?m.show():m.hide(),a(n.body).trigger("wc_gzd_admin_shipment_modal_show_if",[o]),o.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_show_if",[o]))}else t.find(":input[data-show-if-"+i+"]").parents(".form-field").hide(),a(this).is(":visible")&&(a(this).is(":checkbox")?a(this).is(":checked")&&t.find(":input[data-show-if-"+i+"]").parents(".form-field").show():("0"!==d&&""!==d&&t.find(":input[data-show-if-"+i+'=""]').parents(".form-field").show(),t.find(":input[data-show-if-"+i+'*="'+d+'"]').parents(".form-field").show())),t.find(":input[data-show-if-"+i+"]").trigger("change"),t.find(".show-more-wrapper").each((function(){var e=a(this),o="none"!==e.find("p.form-field").css("display"),n=!!e.data("trigger")&&t.find(e.data("trigger"));n.length>0&&(o?n.show():n.hide())}))},i.prototype.onClose=function(e,a){var o=e.data.adminShipmentModal;-1!==a.indexOf(o.modalId)&&o.$modal&&o.$modal.length>0&&o.$modal.off("click.gzd-modal-"+o.modalId)},i.prototype.onOpen=function(e,o){var t=e.data.adminShipmentModal;-1!==o.indexOf(t.modalId)&&(t.setup(),t.$modal=a("."+t.modalClass),t.$modal.data("self",t),t.loadAsync?(params={action:t.getAction("load"),reference_id:t.referenceId,security:t.getNonce("load")},t.doAjax(params,t.onLoadSuccess)):t.initData(),a(n.body).trigger("wc_gzd_admin_shipment_modal_open",[t]),t.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_open",[t]))},i.prototype.onLoadSuccess=function(e,o){o.initData(),a(n.body).trigger("wc_gzd_admin_shipment_modal_after_load_success",[e,o]),o.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_after_load_success",[e,o])},i.prototype.onAjaxSuccess=function(e,a){},i.prototype.onAjaxError=function(e,a){},i.prototype.getModalMainContent=function(){return this.$modal.find("article")},i.prototype.doAjax=function(e,o,i){var d=this,r=d.getModalMainContent();o=o||d.onAjaxSuccess,i=i||d.onAjaxError,e.hasOwnProperty("reference_id")||(e.reference_id=d.referenceId),d.$modal.find(".wc-backbone-modal-content").block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),d.$modal.find(".notice-wrapper").empty(),a.ajax({type:"POST",url:d.params.ajax_url,data:e,success:function(e){e.success?(e.fragments&&a.each(e.fragments,(function(e,o){a(e).replaceWith(o)})),d.$modal.find("#btn-ok").prop("disabled",!1),d.$modal.find(".wc-backbone-modal-content").unblock(),o.apply(d,[e,d]),t.admin.shipments&&t.admin.shipments.refresh(e),a(n.body).trigger("wc_gzd_admin_shipment_modal_ajax_success",[e,d]),d.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_ajax_success",[e,d]),e.fragments&&d.afterRefresh()):(d.$modal.find("#btn-ok").prop("disabled",!1),d.$modal.find(".wc-backbone-modal-content").unblock(),i.apply(d,[e,d]),d.printNotices(r,e),r.animate({scrollTop:0},500),a(n.body).trigger("wc_gzd_admin_shipment_modal_ajax_error",[e,d]),d.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_ajax_error",[e,d]))},error:function(e){},dataType:"json"})},i.prototype.afterRefresh=function(){0===this.$modal.find(".notice-wrapper").length&&this.getModalMainContent().prepend('<div class="notice-wrapper"></div>'),a(n.body).trigger("wc-enhanced-select-init"),a(n.body).trigger("wc-init-datepickers"),a(n.body).trigger("init_tooltips")},i.prototype.initData=function(){var e=this;e.$modal=a("."+e.modalClass),e.$modal.data("self",e),e.afterRefresh(),e.$modal.on("click.gzd-modal-"+e.modalId,"#btn-ok",{adminShipmentModal:e},e.onSubmit),e.$modal.on("touchstart.gzd-modal-"+e.modalId,"#btn-ok",{adminShipmentModal:e},e.onSubmit),e.$modal.on("keydown.gzd-modal-"+e.modalId,{adminShipmentModal:e},e.onKeyDown),e.$modal.on("click.gzd-modal-"+e.modalId,".notice .notice-dismiss",{adminShipmentModal:e},e.onRemoveNotice),e.$modal.on("change.gzd-modal-"+e.modalId,":input[id]",{adminShipmentModal:e},e.onChangeField),e.$modal.on("click.gzd-modal-"+e.modalId,".show-more",{adminShipmentModal:e},e.onExpandMore),e.$modal.on("click.gzd-modal-"+e.modalId,".show-fewer",{adminShipmentModal:e},e.onHideMore),a(n.body).trigger("wc_gzd_admin_shipment_modal_after_init_data",[e]),e.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_after_init_data",[e]),e.$modal.find(":input:visible").trigger("change",[e])},i.prototype.printNotices=function(e,o){var n=this;o.hasOwnProperty("message")?n.addNotice(o.message,"error",e):o.hasOwnProperty("messages")&&a.each(o.messages,(function(o,t){"string"==typeof t||t instanceof String?n.addNotice(t,"error",e):a.each(t,(function(a,t){n.addNotice(t,"soft"===o?"warning":o,e)}))}))},i.prototype.onSubmitSuccess=function(e,o){var i=o.getModalMainContent();e.hasOwnProperty("messages")&&(e.messages.hasOwnProperty("error")||e.messages.hasOwnProperty("soft"))?(o.printNotices(i,e),o.$modal.find("footer").find("#btn-ok").addClass("modal-close").attr("id","btn-close").text(o.params.i18n_modal_close)):o.$modal.find(".modal-close").trigger("click"),e.hasOwnProperty("shipment_id")&&a("div#shipment-"+e.shipment_id).length>0&&t.admin.shipments.initShipment(e.shipment_id),a(n.body).trigger("wc_gzd_admin_shipment_modal_after_submit_success",[e,o]),o.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_after_submit_success",[e,o])},i.prototype.getCleanId=function(e=!1){var a=this.modalClass.split("-").join("_").replace("_modal_","_");return e&&(a=a.replace("wc_gzd_","").replace("wc_gzdp_","")),a},i.prototype.getNonceParams=function(){return o.hasOwnProperty(this.nonceParams)?o[this.nonceParams]:{}},i.prototype.getNonce=function(e){var a=this.getCleanId(!0)+"_"+e+"_nonce",o=this.getNonceParams();return o.hasOwnProperty(a)?o[a]:this.params[e+"_nonce"]},i.prototype.getAction=function(e){return this.getCleanId().replace("wc_","woocommerce_")+"_"+e},i.prototype.onKeyDown=function(a){var o=a.data.adminShipmentModal;13!==(a.keyCode||a.which)||a.target.tagName&&("input"===a.target.tagName.toLowerCase()||"textarea"===a.target.tagName.toLowerCase())||o.onSubmit.apply(o.$modal.find("button#btn-ok"),[e])},i.prototype.getFormData=function(e){var o={};return e.find(".show-more-wrapper").each((function(){a(this).is(":visible")||a(this).addClass("show-more-wrapper-force-show").show()})),a.each(e.find(":input").serializeArray(),(function(n,t){var i=e.find(':input[name="'+t.name+'"]');if(i&&!i.is(":visible")&&"hidden"!==i.attr("type"))return!0;-1!==t.name.indexOf("[]")?(t.name=t.name.replace("[]",""),o[t.name]=a.makeArray(o[t.name]),o[t.name].push(t.value)):o[t.name]=t.value})),e.find(".show-more-wrapper-force-show").each((function(){a(this).removeClass("show-more-wrapper-force-show").hide()})),o},i.prototype.onSubmit=function(e){var a=e.data.adminShipmentModal,o=a.getModalMainContent().find("form"),n=a.getFormData(o),t=a.$modal.find("#btn-ok");t.length>0&&t.prop("disabled",!0),n.security=a.getNonce("submit"),n.reference_id=a.referenceId,n.action=a.getAction("submit"),a.doAjax(n,a.onSubmitSuccess),e.preventDefault(),e.stopPropagation()},i.prototype.addNotice=function(e,a,o){o.find(".notice-wrapper").append('<div class="notice is-dismissible notice-'+a+'"><p>'+e+'</p><button type="button" class="notice-dismiss"></button></div>')},i.prototype.response=function(e,o,t){var i=e.data.adminShipmentModal;-1!==o.indexOf(i.modalId)&&(a(n.body).trigger("wc_gzd_admin_shipment_modal_response",[i,t]),i.$modalTrigger.trigger("wc_gzd_admin_shipment_modal_response",[i,t]))},a.fn.wc_gzd_admin_shipment_modal=function(){return this.each((function(){return new i(a(this)),this}))}}(jQuery,window,document,window.germanized),((window.wcGzdShipments=window.wcGzdShipments||{}).static=window.wcGzdShipments.static||{})["admin-shipment-modal"]={};