(function(a,l){"use strict";const r={state:{widgets:[],selectedId:null},init(){if(!l.interact){console.warn("[Apollo] interact.js n\xE3o carregado.");return}this.state.widgets=this.deserialize(l.apolloBuilder.layout),this.bindToolbar(),this.renderStage(),this.makeDraggable()},bindToolbar(){a("#apollo-builder-save").on("click",()=>this.persist()),a("#apollo-builder-export").on("click",()=>this.export()),a("#apollo-builder-import").on("change",e=>{const t=e.target.files[0];if(!t)return;const i=new FileReader;i.onload=o=>{try{const s=JSON.parse(o.target.result);this.state.widgets=this.deserialize(s),this.renderStage(),this.persist()}catch{alert("JSON inv\xE1lido.")}},i.readAsText(t)}),a("#apollo-widget-library").on("click",".apollo-widget-item",e=>{const t=a(e.currentTarget).data("widget");this.addWidget(t)})},addWidget(e){const t=`widget-${Date.now()}`;this.state.widgets.push({id:t,id_base:e,position:{x:60,y:60,width:280,height:200,z:this.nextZIndex()},settings:{title:"Nova nota",content:"Digite aqui...",color:"#fef3c7"}}),this.renderStage()},nextZIndex(){return this.state.widgets.length===0?10:Math.max(...this.state.widgets.map(e=>e.position.z||10))+2},renderStage(){const e=a("#apollo-builder-stage");if(e.empty(),this.state.widgets.length===0){e.append('<p class="apollo-stage-empty">Arraste widgets para c\xE1 e posicione-os livremente.</p>');return}this.state.widgets.forEach(t=>{const i=a(`
                    <article
                        class="apollo-widget-instance"
                        data-id="${t.id}"
                        data-widget="${t.id_base}"
                        style="left:${t.position.x}px;top:${t.position.y}px;width:${t.position.width}px;height:${t.position.height}px;z-index:${t.position.z};"
                    >
                        <div class="apollo-widget-instance__chrome">
                            <button class="apollo-widget-instance__close" aria-label="Remover">\xD7</button>
                        </div>
                        <div class="apollo-widget-instance__content"></div>
                    </article>
                `);i.find(".apollo-widget-instance__close").on("click",()=>{this.state.widgets=this.state.widgets.filter(o=>o.id!==t.id),this.renderStage()}),i.on("click",()=>this.selectWidget(t.id)),e.append(i),this.renderWidgetContent(i.find(".apollo-widget-instance__content"),t)})},renderWidgetContent(e,t){t.id_base==="apollo_sticky_note"?e.html(`
                    <div class="apollo-sticky-note" style="background:${t.settings.color}">
                        <header class="apollo-sticky-note__header">
                            <span class="apollo-sticky-note__pin"></span>
                            <h3 class="apollo-sticky-note__title">${t.settings.title}</h3>
                        </header>
                        <div class="apollo-sticky-note__content">
                            <p>${t.settings.content.replace(/\n/g,"<br>")}</p>
                        </div>
                    </div>
                `):e.text(t.id_base)},selectWidget(e){this.state.selectedId=e,a(".apollo-widget-instance").removeClass("is-selected"),a(`.apollo-widget-instance[data-id="${e}"]`).addClass("is-selected"),this.renderInspector()},renderInspector(){const e=a("#apollo-inspector-panel"),t=this.state.widgets.find(i=>i.id===this.state.selectedId);if(!t){e.html("<p>Selecione um widget para editar.</p>");return}e.html(`
                <div class="apollo-inspector-group">
                    <label>T\xEDtulo</label>
                    <input type="text" id="apollo-widget-title" value="${t.settings.title}">
                </div>
                <div class="apollo-inspector-group">
                    <label>Conte\xFAdo</label>
                    <textarea id="apollo-widget-content" rows="4">${t.settings.content}</textarea>
                </div>
                <div class="apollo-inspector-group">
                    <label>Cor</label>
                    <input type="color" id="apollo-widget-color" value="${t.settings.color}">
                </div>
                <div class="apollo-inspector-group">
                    <label>Z Index</label>
                    <input type="number" id="apollo-widget-z" value="${t.position.z}">
                </div>
            `),e.find("#apollo-widget-title").on("input",i=>{t.settings.title=i.target.value,this.renderStage(),this.selectWidget(t.id)}),e.find("#apollo-widget-content").on("input",i=>{t.settings.content=i.target.value,this.renderStage(),this.selectWidget(t.id)}),e.find("#apollo-widget-color").on("change",i=>{t.settings.color=i.target.value,this.renderStage(),this.selectWidget(t.id)}),e.find("#apollo-widget-z").on("change",i=>{t.position.z=parseInt(i.target.value,10)||1,this.renderStage(),this.selectWidget(t.id)})},makeDraggable(){document.querySelector("#apollo-builder-stage")&&l.interact(".apollo-widget-instance").draggable({modifiers:[l.interact.modifiers.restrictRect({restriction:"parent",endOnly:!0})],listeners:{move:t=>{const i=t.target,o=i.dataset.id,s=this.state.widgets.find(n=>n.id===o);s&&(s.position.x+=t.dx,s.position.y+=t.dy,i.style.transform=`translate(${s.position.x}px, ${s.position.y}px)`)},end:t=>{const i=t.target,o=i.dataset.id,s=this.state.widgets.find(n=>n.id===o);s&&(i.style.transform="",i.style.left=`${s.position.x}px`,i.style.top=`${s.position.y}px`)}}}).resizable({edges:{left:!0,right:!0,bottom:!0,top:!0}}).on("resizemove",t=>{const i=t.target,o=i.dataset.id,s=this.state.widgets.find(n=>n.id===o);s&&(s.position.width=t.rect.width,s.position.height=t.rect.height,s.position.x=t.rect.left,s.position.y=t.rect.top,i.style.width=`${s.position.width}px`,i.style.height=`${s.position.height}px`,i.style.left=`${s.position.x}px`,i.style.top=`${s.position.y}px`)})},persist(){const e=this.serialize();a.ajax({url:l.apolloBuilder.restUrl,method:"POST",beforeSend:t=>{t.setRequestHeader("X-WP-Nonce",l.apolloBuilder.nonce)},data:{layout:JSON.stringify(e),user_id:l.apolloBuilder.currentUser}}).done(()=>{if(l.wp&&l.wp.data&&l.wp.data.dispatch)try{l.wp.data.dispatch("core/notices").createNotice("success","Layout salvo!",{type:"snackbar"})}catch(t){console.debug("Notice dispatch failed",t)}}).fail(()=>{alert("Erro ao salvar layout")})},serialize(){return{widgets:this.state.widgets.map(e=>({id:e.id,id_base:e.id_base,settings:e.settings,position:e.position})),apollo:{absolute:!0}}},deserialize(e){return!e||!Array.isArray(e.widgets)?[]:e.widgets.map(t=>({id:t.id,id_base:t.id_base,settings:t.settings||{},position:Object.assign({x:40,y:40,width:260,height:180,z:10},t.position||{})}))},export(){const e=JSON.stringify(this.serialize(),null,2),t=new Blob([e],{type:"application/json"}),i=URL.createObjectURL(t),o=document.createElement("a");o.href=i,o.download=`apollo-layout-${Date.now()}.json`,o.click(),URL.revokeObjectURL(i)}};a(document).ready(()=>r.init())})(jQuery,window);

