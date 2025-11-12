class ApolloOnboardingChat{constructor(){this.data=this.loadInitialData(),this.config=this.data.config,this.progress=this.data.progress||{},this.currentStep=this.data.currentStep||"ask_name",this.userId=this.data.userId,this.nonce=this.data.nonce,this.steps=this.defineSteps(),this.responses={},this.init()}init(){this.setupEventListeners(),this.startOnboarding()}loadInitialData(){const t=document.getElementById("onboardingData");if(t)try{return JSON.parse(t.textContent)}catch(e){console.error("Error parsing onboarding data:",e)}return{}}defineSteps(){return{ask_name:{question:"Qual \xE9 o seu nome?",type:"text_input",validation:{min_length:2},field:"name"},ask_industry:{question:"Voc\xEA trabalha na ind\xFAstria da m\xFAsica/eventos?",type:"single_choice",options:["Yes","No","Future yes!"],field:"industry"},ask_roles:{question:"Quais s\xE3o seus roles? (pode escolher v\xE1rios)",type:"multi_choice",condition:t=>["Yes","Future yes!"].includes(t.industry),options:["DJ","PRODUCER","CULTURAL PRODUCER","MUSIC PRODUCER","PHOTOGRAPHER","VISUALS & DIGITAL ART","BAR TEAM","FINANCE TEAM","GOVERNMENT","BUSINESS PERSON","HOSTESS","PROMOTER","INFLUENCER"],field:"roles"},ask_memberships:{question:"De quais locais/n\xFAcleos voc\xEA faz parte?",type:"dynamic_multi_choice",field:"member_of"},ask_contacts:{question:"Como podemos te contactar?",type:"contact_form",fields:["whatsapp","instagram"],validation:{whatsapp:{required:!0,mask:"+55 (##) #####-####"},instagram:{required:!0,regex:/^@?[a-zA-Z0-9._]{1,30}$/}}},verification_rules:{question:"Verifica\xE7\xE3o Instagram",type:"verification_info",field:"verification"},summary_submit:{question:"Confirmar dados",type:"summary",field:"submit"}}}setupEventListeners(){document.addEventListener("click",t=>{t.target.classList.contains("chip")?this.handleChipClick(t.target):t.target.classList.contains("send-button")?this.handleSendClick():t.target.classList.contains("continue-button")&&!t.target.classList.contains("request-dm-button")?this.nextStep():t.target.classList.contains("request-dm-button")&&this.handleRequestDm(t.target)}),document.addEventListener("keypress",t=>{t.key==="Enter"&&t.target.classList.contains("text-input")&&this.handleSendClick()})}async startOnboarding(){await this.delay(500),this.showStep(this.currentStep)}async showStep(t){var s;const e=this.steps[t];if(e){if(e.condition&&!e.condition(this.responses)){this.nextStep();return}this.showTyping(),await this.delay(((s=this.config.messages)==null?void 0:s.typing_delay)||1500),this.hideTyping(),this.addBotMessage(e.question),await this.delay(300),this.generateInput(e),this.updateProgress(t)}}generateInput(t){const e=document.getElementById("chatInputArea");let s="";switch(t.type){case"text_input":s=this.generateTextInput(t);break;case"single_choice":s=this.generateChoiceChips(t.options,!1);break;case"multi_choice":s=this.generateChoiceChips(t.options,!0);break;case"dynamic_multi_choice":s=this.generateDynamicChoices(t);break;case"contact_form":s=this.generateContactForm(t);break;case"verification_info":s=this.generateVerificationInfo();break;case"summary":s=this.generateSummary();break}e.innerHTML=s;const a=e.querySelector("input, .chip");a&&a.focus(),this.applyInputMasks()}generateTextInput(t){var e;return`
            <div class="input-group">
                <input type="text" 
                       class="text-input" 
                       placeholder="${t.placeholder||"Digite sua resposta..."}"
                       data-field="${t.field}"
                       required="${((e=t.validation)==null?void 0:e.required)||!1}">
                <button type="button" class="send-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        `}generateChoiceChips(t,e=!1){return`
            <div class="choice-chips">
                ${t.map(a=>`<div class="chip ${e?"multi-select":""}" 
                  data-value="${a}" 
                  data-multi="${e}"
                  role="button" 
                  tabindex="0">
                ${a}
             </div>`).join("")}
            </div>
            ${e?'<button type="button" class="continue-button" style="margin-top: 12px; opacity: 0.5;" disabled>Continuar</button>':""}
        `}async generateDynamicChoices(t){var a,i;const e=await this.loadMembershipOptions();let s='<div class="choice-chips">';return(a=e.nucleos)!=null&&a.length&&(s+='<div class="chip-category">N\xFAcleos:</div>',e.nucleos.forEach(n=>{s+=`<div class="chip multi-select" data-value="nucleo:${n.id}" data-multi="true">${n.title}</div>`})),(i=e.locais)!=null&&i.length&&(s+='<div class="chip-category">Locais:</div>',e.locais.forEach(n=>{s+=`<div class="chip multi-select" data-value="local:${n.id}" data-multi="true">${n.title}</div>`})),s+="</div>",s+='<button type="button" class="continue-button" style="margin-top: 12px;">Continuar</button>',s}generateContactForm(t){return`
            <div class="contact-form">
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="tel" 
                           class="form-input whatsapp-input" 
                           placeholder="+55 (11) 99999-9999"
                           data-field="whatsapp"
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="text" 
                           class="form-input instagram-input" 
                           placeholder="@seuusuario"
                           data-field="instagram"
                           required>
                </div>
                <button type="button" class="continue-button" style="margin-top: 12px;">Continuar</button>
            </div>
        `}generateVerificationInfo(){const t=this.normalizeInstagram(this.responses.instagram),e=this.buildVerifyToken(t),s=`eu sou @${t} no apollo :: ${e}`;return`
            <div class="verification-info">
                <div class="verification-card">
                    <h4>Verifica\xE7\xE3o Instagram</h4>
                    <p>Para verificar sua conta, envie uma DM no Instagram oficial com a frase abaixo:</p>
                    <div class="verification-text" id="verification-phrase" style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin: 12px 0; font-family: monospace; word-break: break-all;">
                        <strong>${s}</strong>
                    </div>
                    <button type="button" class="copy-button" onclick="navigator.clipboard.writeText('${s}').then(() => alert('Frase copiada!'))" style="margin: 8px 0; padding: 8px 16px; background: #0078d4; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        \u{1F4CB} Copiar frase
                    </button>
                    <p><small>Envie esta frase por DM no Instagram oficial. Aguarde a valida\xE7\xE3o manual.</small></p>
                </div>
                <button type="button" class="request-dm-button continue-button" style="margin-top: 12px;" data-token="${e}">
                    Solicitar verifica\xE7\xE3o via DM
                </button>
                <button type="button" class="continue-button" style="margin-top: 12px; display: none;" id="finalize-btn">
                    Finalizar Cadastro
                </button>
            </div>
        `}generateSummary(){return`
            <div class="summary-card">
                <h4>Confirme seus dados:</h4>
                <div class="summary-content">
                    ${this.buildSummary()}
                </div>
                <button type="button" class="continue-button confirm-submit" style="margin-top: 12px;">
                    \u2728 Confirmar e Finalizar
                </button>
            </div>
        `}handleChipClick(t){const e=t.dataset.multi==="true",s=t.dataset.value;e?(t.classList.toggle("selected"),this.updateContinueButton()):(t.parentElement.querySelectorAll(".chip").forEach(a=>a.classList.remove("selected")),t.classList.add("selected"),setTimeout(()=>{this.collectResponse(),this.nextStep()},300))}handleSendClick(){const t=document.querySelector(".text-input");if(!t)return;const e=t.value.trim();if(!e||!this.validateInput(t))return;this.addUserMessage(e);const s=t.dataset.field;this.responses[s]=e,document.getElementById("chatInputArea").innerHTML="",setTimeout(()=>this.nextStep(),500)}collectResponse(){var s,a;const t=this.getCurrentStepName(),e=this.steps[t];if(e.type==="single_choice"||e.type==="multi_choice"){const i=Array.from(document.querySelectorAll(".chip.selected")).map(n=>n.dataset.value);e.type==="single_choice"?(this.responses[e.field]=i[0],this.addUserMessage(i[0])):(this.responses[e.field]=i,this.addUserMessage(i.join(", ")))}else if(e.type==="contact_form"){const i=(s=document.querySelector('[data-field="whatsapp"]'))==null?void 0:s.value,n=(a=document.querySelector('[data-field="instagram"]'))==null?void 0:a.value;i&&(this.responses.whatsapp=this.normalizeWhatsapp(i)),n&&(this.responses.instagram=this.normalizeInstagram(n)),this.addUserMessage(`WhatsApp: ${i}, Instagram: ${n}`)}}validateInput(t){var i;const e=this.getCurrentStepName(),s=this.steps[e],a=t.value.trim();return(i=s.validation)!=null&&i.min_length&&a.length<s.validation.min_length?(this.showValidationError(`M\xEDnimo ${s.validation.min_length} caracteres`),!1):t.dataset.field==="instagram"&&!/^@?[a-zA-Z0-9._]{1,30}$/.test(a)?(this.showValidationError("Instagram inv\xE1lido"),!1):!0}showValidationError(t){const e=document.createElement("div");e.className="validation-error",e.textContent=t,e.style.cssText="color: #dc2626; font-size: 12px; margin-top: 4px;";const s=document.getElementById("chatInputArea"),a=s.querySelector(".validation-error");a&&a.remove(),s.appendChild(e),setTimeout(()=>e.remove(),3e3)}updateContinueButton(){const t=document.querySelector(".continue-button"),e=document.querySelectorAll(".chip.selected");t&&(t.disabled=e.length===0,t.style.opacity=e.length>0?"1":"0.5")}nextStep(){const t=Object.keys(this.steps),e=t.indexOf(this.currentStep);e<t.length-1?(this.currentStep=t[e+1],setTimeout(()=>this.showStep(this.currentStep),500)):this.completeOnboarding()}getCurrentStepName(){return this.currentStep}async completeOnboarding(){this.showTyping(),await this.saveProgress(),this.hideTyping(),this.addBotMessage("Perfeito! Seu cadastro foi enviado para verifica\xE7\xE3o. \u{1F389}"),await this.delay(1e3),this.addBotMessage("Utilidades especiais chegando \u2014 siga @apollo no IG para ficar por dentro! \u{1F4F1}"),setTimeout(()=>{window.location.href="/verificacao/"},3e3)}async saveProgress(){var t;try{const e=await fetch(`${((t=window.apolloOnboarding)==null?void 0:t.apiUrl)||"/wp-json/apollo/v1/"}onboarding/complete`,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":this.nonce},body:JSON.stringify({responses:this.responses,verify_token:this.buildVerifyToken(this.normalizeInstagram(this.responses.instagram))})});if(!e.ok)throw new Error("Network response was not ok");return await e.json()}catch(e){console.error("Error saving progress:",e),this.addBotMessage("Erro ao salvar. Tente novamente.")}}addBotMessage(t){const e=document.getElementById("chatMessages"),s=`
            <div class="message-bubble bot-message">
                <div class="message-content">
                    <p>${t}</p>
                </div>
                <div class="message-timestamp">
                    ${new Date().toLocaleTimeString("pt-BR",{hour:"2-digit",minute:"2-digit"})}
                </div>
            </div>
        `;e.insertAdjacentHTML("beforeend",s),this.scrollToBottom()}addUserMessage(t){const e=document.getElementById("chatMessages"),s=`
            <div class="message-bubble user-message">
                <div class="message-content">
                    <p>${t}</p>
                </div>
                <div class="message-timestamp">
                    ${new Date().toLocaleTimeString("pt-BR",{hour:"2-digit",minute:"2-digit"})}
                </div>
            </div>
        `;e.insertAdjacentHTML("beforeend",s),this.scrollToBottom()}showTyping(){const t=document.getElementById("typingIndicator");t.style.display="block",this.scrollToBottom()}hideTyping(){const t=document.getElementById("typingIndicator");t.style.display="none"}scrollToBottom(){const t=document.getElementById("chatMessages");t.scrollTop=t.scrollHeight}updateProgress(t){const e=Object.keys(this.steps),s=e.indexOf(t),a=(s+1)/e.length*100,i=document.getElementById("progressFill"),n=document.getElementById("progressText");i&&(i.style.width=`${a}%`),n&&(n.textContent=`Passo ${s+1} de ${e.length}`)}applyInputMasks(){const t=document.querySelector(".whatsapp-input");t&&t.addEventListener("input",s=>{let a=s.target.value.replace(/\D/g,"");a.length>=11&&(a=a.replace(/^(\d{2})(\d{2})(\d{5})(\d{4})/,"+55 ($2) $3-$4")),s.target.value=a});const e=document.querySelector(".instagram-input");e&&e.addEventListener("input",s=>{let a=s.target.value;a&&!a.startsWith("@")&&(s.target.value="@"+a)})}normalizeInstagram(t){return t?t.replace(/^@/,"").toLowerCase():""}normalizeWhatsapp(t){const e=t.replace(/\D/g,"");return e.length===11?"+55"+e:"+"+e}buildVerifyToken(t){const e=new Date,s=e.getFullYear(),a=String(e.getMonth()+1).padStart(2,"0"),i=String(e.getDate()).padStart(2,"0");return`${s}${a}${i}${t.toLowerCase()}`}buildSummary(){const{name:t,industry:e,roles:s,member_of:a,whatsapp:i,instagram:n}=this.responses;let o=`<p><strong>Nome:</strong> ${t}</p>`;return o+=`<p><strong>Ind\xFAstria:</strong> ${e}</p>`,s!=null&&s.length&&(o+=`<p><strong>Roles:</strong> ${s.join(", ")}</p>`),a!=null&&a.length&&(o+=`<p><strong>Membro de:</strong> ${a.join(", ")}</p>`),o+=`<p><strong>WhatsApp:</strong> ${i}</p>`,o+=`<p><strong>Instagram:</strong> @${this.normalizeInstagram(n)}</p>`,o}async loadMembershipOptions(){var t;try{return await(await fetch(`${((t=window.apolloOnboarding)==null?void 0:t.apiUrl)||"/wp-json/apollo/v1/"}onboarding/options`)).json()}catch(e){return console.error("Error loading membership options:",e),{nucleos:[],locais:[]}}}async handleRequestDm(t){var e;t.disabled=!0,t.textContent="Solicitando...";try{const s=await fetch(`${((e=window.apolloOnboarding)==null?void 0:e.apiUrl)||"/wp-json/apollo/v1/"}onboarding/verify/request-dm`,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":this.nonce}}),a=await s.json();if(a.success){t.textContent="\u2713 Solicita\xE7\xE3o enviada",t.style.background="#28a745",t.disabled=!0;const i=document.getElementById("finalize-btn");i&&(i.style.display="block"),window.ApolloAnalytics&&window.ApolloAnalytics.track("verification_dm_requested",{user_id:this.userId,token:a.token}),this.addBotMessage("Aguardando valida\xE7\xE3o manual. Voc\xEA receber\xE1 um e-mail quando sua conta for verificada.")}else{t.disabled=!1,t.textContent="Solicitar verifica\xE7\xE3o via DM";const i=a.message||"Erro ao solicitar verifica\xE7\xE3o";s.status===429?alert("Aguarde um minuto antes de solicitar novamente."):alert(i)}}catch(s){console.error("Error requesting DM verification:",s),t.disabled=!1,t.textContent="Solicitar verifica\xE7\xE3o via DM",alert("Erro ao solicitar verifica\xE7\xE3o. Tente novamente.")}}delay(t){return new Promise(e=>setTimeout(e,t))}}document.addEventListener("DOMContentLoaded",()=>{new ApolloOnboardingChat});

