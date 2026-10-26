export function calculatorWizard(catalog, endpoints) {
    return {
        catalog, endpoints, type: null, step: 1, property: '', area: '', packageId: '', bedrooms: 1,
        layout: 'straight', dimensions: {base: 0, wall: 0, tall: 0, island: 0, countertop: 0},
        materialIds: {carcass: '', finish: '', countertop: ''}, selectedServices: [], quantities: {},
        selectedAddons: [], addonQuantities: {}, rooms: [], result: null, errors: [], busy: false, saving: false,
        timer: null, controller: null, sequence: 0, unavailable: false,
        init() { this.$watch('fingerprint', () => this.schedule()); },
        get typeRecord() { return this.catalog.types.find(type => type.code === this.type); },
        get rule() { return this.catalog.rules.find(rule => Number(rule.calculator_type_id) === Number(this.typeRecord?.id)); },
        options(key) { return this.catalog[key].filter(option => Number(option.calculator_type_id) === Number(this.typeRecord?.id)); },
        materialOptions(category) { return this.options('materials').filter(option => option.config?.category === category); },
        get steps() { return this.type === 'home' ? ['Property','Area','Package','Requirements','Add-ons','Estimate'] : this.type === 'kitchen' ? ['Layout','Dimensions','Materials','Accessories','Estimate'] : ['Rooms & work','Add-ons','Estimate']; },
        get fingerprint() { return JSON.stringify(this.payload()); },
        choose(type) {
            this.type = type; this.step = 1; this.result = null; this.errors = []; this.property = ''; this.area = '';
            this.packageId = this.options('packages')[0]?.id || ''; this.selectedServices = []; this.quantities = {};
            this.selectedAddons = []; this.addonQuantities = {}; this.rooms = [];
            this.dimensions = {base: 0,wall: 0,tall: 0,island: 0,countertop: 0};
            this.materialIds = {carcass:'',finish:'',countertop:''};
            this.unavailable = !this.catalog.enabled || !this.rule || (type === 'home' && !this.options('packages').length);
            this.$nextTick(() => { this.$refs.wizard?.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'}); this.focusStep(); });
        },
        service(id) { return this.options('services').find(service => Number(service.id) === Number(id)); },
        unitLabel(unit) { return ({sqft:'sq ft',running_ft:'running ft',item:'items'})[unit] || unit; },
        money(value) { return new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR',maximumFractionDigits:0}).format(value / 100); },
        range(value) { return value ? this.money(value.min)+' – '+this.money(value.max) : '—'; },
        addRoom() { this.rooms.push({key:crypto.randomUUID(),name:'Bedroom',items:[]}); },
        addItem(room) { room.items.push({key:crypto.randomUUID(),service_id:'',quantity:''}); },
        payload() {
            const payload = {type:this.type, addons:this.selectedAddons.map(id=>({addon_id:Number(id),quantity:String(this.addonQuantities[id]??1)}))};
            if(this.type==='home') Object.assign(payload,{property_type_id:this.property?Number(this.property):null,area:String(this.area||0),package_id:this.packageId?Number(this.packageId):null,bedrooms:Number(this.bedrooms),items:this.selectedServices.map(id=>({service_id:Number(id),quantity:String(this.quantities[id]??(this.service(id)?.unit==='item'?1:0)),room:this.service(id)?.name||'Home requirements'}))});
            if(this.type==='kitchen') Object.assign(payload,{layout:this.layout,dimensions:Object.fromEntries(Object.entries(this.dimensions).map(([key,value])=>[key,String(value||0)])),materials:Object.values(this.materialIds).filter(Boolean).map(Number)});
            if(this.type==='room') payload.items=this.rooms.flatMap(room=>room.items.map(item=>({service_id:Number(item.service_id),quantity:String(item.quantity||0),room:room.name.trim()})));
            return payload;
        },
        ready() {
            if(!this.type || this.unavailable) return false;
            const input=this.payload();
            if(this.type==='home') return !!input.property_type_id && !!input.package_id && Number(input.area)>0;
            if(this.type==='kitchen') return input.materials.length===3 && Number(input.dimensions.countertop)>0 && Object.entries(input.dimensions).some(([key,value])=>key!=='countertop' && Number(value)>0);
            return input.items.length>0 && input.items.every(item=>item.service_id && Number(item.quantity)>0 && item.room);
        },
        schedule() {
            clearTimeout(this.timer); this.sequence++; this.controller?.abort(); this.result=null; this.busy=false;
            if(!this.ready()) return;
            this.timer=setTimeout(()=>this.preview(),450);
        },
        async request(url, payload, signal) {
            const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(payload),signal});
            const data=await response.json().catch(()=>({message:'The studio could not respond. Please refresh and try again.'}));
            if(!response.ok) throw new Error(data.errors?Object.values(data.errors).flat().join(' '):(data.message||'Please try again.'));
            return data;
        },
        async preview() {
            const sequence=++this.sequence; this.controller?.abort(); this.controller=new AbortController();this.busy=true;this.errors=[];
            try { const result=await this.request(this.endpoints.preview,this.payload(),this.controller.signal);if(sequence===this.sequence)this.result=result; }
            catch(error) { if(error.name!=='AbortError' && sequence===this.sequence)this.errors=[error.message]; }
            finally { if(sequence===this.sequence)this.busy=false; }
        },
        focusStep() { this.$nextTick(()=>{this.$refs.wizard?.querySelector('[data-step-title]')?.focus({preventScroll:true});globalThis.window?.dispatchEvent(new Event('studio:layout'));}); },
        next() {
            this.errors=[];
            const fields=[...this.$refs.wizard.querySelectorAll('input,select')].filter(field=>field.offsetParent!==null);
            for(const field of fields){if(!field.reportValidity())return;}
            if(this.type==='home' && this.step===1 && !this.property){this.errors=['Select your property type.'];return;}
            if(this.type==='kitchen' && this.step===2 && !(Number(this.dimensions.countertop)>0 && ['base','wall','tall','island'].some(key=>Number(this.dimensions[key])>0))){this.errors=['Enter measured cabinet quantities and countertop area.'];return;}
            if(this.type==='kitchen' && this.step===3 && Object.values(this.materialIds).some(value=>!value)){this.errors=['Choose a carcass, shutter finish and countertop material.'];return;}
            if(this.type==='room' && this.step===1 && !this.ready()){this.errors=['Add at least one room with a work item and measured quantity.'];return;}
            this.step=Math.min(this.steps.length,this.step+1);this.focusStep();
            if(this.step===this.steps.length && this.ready()){clearTimeout(this.timer);this.preview();}
        },
        async save() {
            if(this.saving || !this.result || this.busy)return;
            this.saving=true;this.errors=[];
            try { const saved=await this.request(this.endpoints.store,this.payload());window.location.assign(saved.url); }
            catch(error) { this.errors=[error.message];this.saving=false; }
        },
    };
}
