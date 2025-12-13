class ApolloModeration{constructor(){this.apiBase="/wp-json/apollo/v1",this.nonce=window.apolloNonce||"",this.init()}init(){document.addEventListener("DOMContentLoaded",()=>{this.bindEvents()})}bindEvents(){document.querySelectorAll(".apollo-approve-btn").forEach(e=>{e.addEventListener("click",t=>this.handleApprove(t))}),document.querySelectorAll(".apollo-reject-btn").forEach(e=>{e.addEventListener("click",t=>this.handleReject(t))}),document.querySelectorAll(".apollo-confirm-reject").forEach(e=>{e.addEventListener("click",t=>this.handleConfirmReject(t))}),document.querySelectorAll(".apollo-resubmit-btn").forEach(e=>{e.addEventListener("click",t=>this.handleResubmit(t))}),document.querySelectorAll(".apollo-submit-btn").forEach(e=>{e.addEventListener("click",t=>this.handleSubmitForReview(t))}),document.querySelectorAll(".apollo-modal-close, .apollo-modal-cancel").forEach(e=>{e.addEventListener("click",t=>this.closeModal(t))}),document.querySelectorAll(".apollo-rejection-modal").forEach(e=>{e.addEventListener("click",t=>{t.target===e&&this.closeModal(t)})})}async handleApprove(e){e.preventDefault();const t=e.target,a=t.dataset.groupId;if(confirm("Tem certeza que deseja aprovar este grupo?")){this.setButtonLoading(t,!0);try{const o=await this.apiCall(`/groups/${a}aprovar`,"POST");o.success?(this.showToast("Grupo aprovado com sucesso!","success"),this.updateGroupStatus(a,"published"),this.hideModeration(a)):this.showToast(o.message||"Erro ao aprovar grupo","error")}catch(o){console.error("Approve error:",o),this.showToast("Erro de conex\xE3o. Tente novamente.","error")}finally{this.setButtonLoading(t,!1)}}}handleReject(e){e.preventDefault();const a=e.target.dataset.groupId,o=document.getElementById(`apollo-rejection-modal-${a}`);if(o){o.style.display="flex";const s=o.querySelector(".apollo-rejection-textarea");s&&setTimeout(()=>s.focus(),100)}}async handleConfirmReject(e){e.preventDefault();const t=e.target,a=t.dataset.groupId,o=document.getElementById(`apollo-rejection-reason-${a}`),s=o?o.value.trim():"";if(!s){this.showToast("Por favor, informe o motivo da rejei\xE7\xE3o.","warning"),o&&o.focus();return}this.setButtonLoading(t,!0);try{const n=await this.apiCall(`/groups/${a}/reject`,"POST",{reason:s});n.success?(this.showToast("Grupo rejeitado com sucesso!","success"),this.updateGroupStatus(a,"rejected",n.standard_message),this.hideModeration(a),this.closeModalById(a)):this.showToast(n.message||"Erro ao rejeitar grupo","error")}catch(n){console.error("Reject error:",n),this.showToast("Erro de conex\xE3o. Tente novamente.","error")}finally{this.setButtonLoading(t,!1)}}async handleResubmit(e){e.preventDefault();const t=e.target,a=t.dataset.groupId;if(confirm("Deseja mover este grupo para rascunho para edi\xE7\xE3o?")){this.setButtonLoading(t,!0);try{const o=await this.apiCall(`/groups/${a}/resubmit`,"POST");o.success?(this.showToast(o.message,"success"),o.redirect_url?setTimeout(()=>{window.location.href=o.redirect_url},1500):this.updateGroupStatus(a,"draft")):this.showToast(o.message||"Erro ao reenviar grupo","error")}catch(o){console.error("Resubmit error:",o),this.showToast("Erro de conex\xE3o. Tente novamente.","error")}finally{this.setButtonLoading(t,!1)}}}async handleSubmitForReview(e){e.preventDefault();const a=e.target.dataset.groupId;confirm("Enviar este grupo para revis\xE3o?")&&this.showToast("Funcionalidade em desenvolvimento","info")}closeModal(e){const t=e.target.closest(".apollo-rejection-modal");t&&(t.style.display="none")}closeModalById(e){const t=document.getElementById(`apollo-rejection-modal-${e}`);t&&(t.style.display="none")}updateGroupStatus(e,t,a=null){const o=document.querySelector(`[data-group-id="${e}"] .apollo-status-badge`);if(o){o.className=o.className.replace(/apollo-status-\w+/g,""),o.classList.add(`apollo-status-${t}`);const s=o.querySelector(".apollo-status-label");if(s){const n={draft:"Rascunho",pending:"Aguardando",pending_review:"Em An\xE1lise",published:"Publicado",rejected:"Rejeitado"};s.textContent=n[t]||"Desconhecido"}if(t==="rejected"&&a)this.addRejectionNotice(o,a,e);else{const n=o.querySelector(".apollo-rejection-notice");n&&n.remove()}}this.updateActionButtons(e,t)}addRejectionNotice(e,t,a){const o=e.querySelector(".apollo-rejection-notice");o&&o.remove();const s=document.createElement("div");s.className="apollo-rejection-notice",s.innerHTML=`
            <div class="apollo-rejection-message">
                ${t}
            </div>
            <div class="apollo-rejection-actions">
                <button type="button" class="apollo-btn apollo-btn-secondary apollo-resubmit-btn" 
                        data-group-id="${a}">
                    Revisar e Reenviar
                </button>
            </div>
        `,e.appendChild(s);const n=s.querySelector(".apollo-resubmit-btn");n&&n.addEventListener("click",r=>this.handleResubmit(r))}updateActionButtons(e,t){const a=document.querySelector(`[data-group-id="${e}"]`),o=a==null?void 0:a.querySelector(".apollo-group-actions");o&&(o.innerHTML="",t==="draft"?o.innerHTML=`
                <a href="/grupo/editar/${e}/" class="apollo-btn apollo-btn-primary">
                    Continuar Editando
                </a>
                <button type="button" class="apollo-btn apollo-btn-secondary apollo-submit-btn" 
                        data-group-id="${e}">
                    Enviar para Revis\xE3o
                </button>
            `:t==="published"&&(o.innerHTML=`
                <a href="/grupo/${e}/" class="apollo-btn apollo-btn-success">
                    Ver Grupo
                </a>
                <a href="/grupo/editar/${e}/" class="apollo-btn apollo-btn-secondary">
                    Editar
                </a>
            `),this.bindEvents())}hideModeration(e){const t=document.querySelector(`[data-group-id="${e}"] .apollo-mod-actions`);t&&(t.style.display="none")}setButtonLoading(e,t){t?(e.disabled=!0,e.dataset.originalText=e.textContent,e.textContent="Processando...",e.classList.add("apollo-loading")):(e.disabled=!1,e.textContent=e.dataset.originalText||e.textContent,e.classList.remove("apollo-loading"))}showToast(e,t="info"){let a=document.getElementById("apollo-toast-container");a||(a=document.createElement("div"),a.id="apollo-toast-container",a.style.cssText=`
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `,document.body.appendChild(a));const o=document.createElement("div"),s={success:"#10b981",error:"#dc2626",warning:"#f59e0b",info:"#3b82f6"};o.style.cssText=`
            background: ${s[t]||s.info};
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `,o.textContent=e,a.appendChild(o),setTimeout(()=>{o.style.transform="translateX(0)"},10),setTimeout(()=>{o.style.transform="translateX(100%)",setTimeout(()=>{o.parentNode&&o.parentNode.removeChild(o)},300)},4e3)}async apiCall(e,t="GET",a=null){const o=`${this.apiBase}${e}`,s={method:t,headers:{"Content-Type":"application/json","X-WP-Nonce":this.nonce}};a&&(t==="POST"||t==="PUT")&&(s.body=JSON.stringify(a));const n=await fetch(o,s);if(!n.ok)throw new Error(`HTTP ${n.status}: ${n.statusText}`);return await n.json()}}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",()=>{new ApolloModeration}):new ApolloModeration;

