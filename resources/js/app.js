import { calculatorWizard } from './calculator';
import { initPremiumMotion } from './premium-motion';
import { initAboutPage } from './about';
import { initConsultation } from './consultation';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';


import { createIcons, MessageCircle, Phone, Scan, Layers, LayoutTemplate, SwatchBook, Gem, DraftingCompass } from 'lucide';
import Swiper from 'swiper';
import { Navigation, Pagination, A11y, Keyboard } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
Alpine.plugin(focus);
Alpine.data('calculatorWizard', calculatorWizard);
window.Alpine = Alpine;
Alpine.start();
createIcons({icons:{ MessageCircle, Phone, Scan, Layers, LayoutTemplate, SwatchBook, Gem, DraftingCompass }});
const header=document.querySelector('.site-header');
const updateHeader=()=>header?.classList.toggle('scrolled',window.scrollY>60);
updateHeader();
window.addEventListener('scroll',updateHeader,{passive:true});
const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
if(document.querySelector('.swiper'))new Swiper('.swiper',{modules:[Navigation,Pagination,A11y,Keyboard],loop:false,keyboard:{enabled:true},speed:reduced.matches?0:600,pagination:{el:'.swiper-pagination',clickable:true},navigation:{nextEl:'.swiper-button-next',prevEl:'.swiper-button-prev'}});
initPremiumMotion();
initAboutPage();
initConsultation();
