import test from 'node:test';
import assert from 'node:assert/strict';
import { calculatorWizard } from '../resources/js/calculator.js';

const catalog={enabled:true,types:[{id:1,code:'home'},{id:2,code:'kitchen'},{id:3,code:'room'}],rules:[{calculator_type_id:1},{calculator_type_id:2},{calculator_type_id:3}],packages:[{id:10,calculator_type_id:1}],properties:[],materials:[],services:[{id:30,calculator_type_id:1,name:'Wardrobe',unit:'sqft'},{id:31,calculator_type_id:3,name:'Cabinetry',unit:'running_ft'}],addons:[{id:40,calculator_type_id:1,unit:'item'}]};
function state(){const wizard=calculatorWizard(structuredClone(catalog),{preview:'/preview',store:'/store'});wizard.$refs={};wizard.$nextTick=callback=>callback();return wizard;}

test('property choice never assumes a floor area and zero quantities stay zero',()=>{
    const wizard=state();wizard.choose('home');wizard.property=20;
    assert.equal(wizard.area,'');assert.equal(wizard.ready(),false);
    wizard.area='1234.50';wizard.selectedServices=['30'];wizard.quantities[30]=0;
    wizard.selectedAddons=['40'];wizard.addonQuantities[40]=0;
    const payload=wizard.payload();assert.equal(payload.area,'1234.50');
    assert.equal(payload.items[0].quantity,'0');assert.equal(payload.addons[0].quantity,'0');
    assert.equal('total' in payload,false);
});
test('room items retain measured quantities and room names',()=>{
    const wizard=state();wizard.choose('room');wizard.addRoom();wizard.rooms[0].name='Home Office';
    wizard.addItem(wizard.rooms[0]);wizard.rooms[0].items[0].service_id=31;wizard.rooms[0].items[0].quantity='8.25';
    assert.deepEqual(wizard.payload().items,[{service_id:31,quantity:'8.25',room:'Home Office'}]);
    assert.equal(wizard.ready(),true);
    wizard.rooms.splice(0,1);assert.equal(wizard.ready(),false);
});
test('an older price response cannot overwrite a newer selection',async()=>{
    const wizard=state();wizard.choose('home');const pending=[];
    wizard.request=()=>new Promise(resolve=>pending.push(resolve));
    const older=wizard.preview();const newer=wizard.preview();
    pending[1]({total:{min:200,max:300}});await newer;
    pending[0]({total:{min:100,max:150}});await older;
    assert.deepEqual(wizard.result.total,{min:200,max:300});assert.equal(wizard.busy,false);
});
