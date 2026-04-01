```markdown
# Local AI UI/UX Overhaul: Strict Atomic Execution Prompt (Technical + Code-Heavy Edition)

## Objective
Generate hyper-granular `TODO.md` with **only executable code tasks** (minimum 80 atomic tasks), then execute every task in strict numbered order with zero exceptions. Deliver production-ready Apple-grade glassmorphism + Lenis + GSAP timelines + AOS triggers using exclusively local project files.

## Context
- Local-only (`c:\laragon\www\ac_beneran_final`).  
- Use only `package.json` libraries, existing CDN references, and built-in knowledge of GSAP/AOS/anime.js/Lenis.  
- No external searches or downloads.

## Mandatory Reading (First Action)
1. Parse `folderstructure.md`.  
2. Read all `docs/` files (if present).  
3. Locate & parse skill file (`skill*.md`).  
4. Parse current `TODO.md`, `README.md`, all `public/css/*.css`, `public/js/*.js`, `resources/css/*.css`, `resources/js/*.js`, and every Blade view.  
5. Audit live UI for misplaced/broken elements.

## Glassmorphism Spec (Exact CSS – Global)
```css
.glass { background:rgba(255,255,255,0.08); backdrop-filter:blur(24px)saturate(180%); border:1px solid rgba(255,255,255,0.18); box-shadow:0 8px 32px -4px rgba(0,0,0,0.12),0 2px 8px -2px rgba(255,255,255,0.3); transition:all .3s cubic-bezier(.4,0,.2,1); }
.glass-hover:hover { transform:scale(1.02) translateY(-2px); backdrop-filter:blur(32px); }
.glass-active:active { transform:scale(0.97) translateY(1px); }
```
Apply `.glass` + `.glass-hover` to every card, button, nav, modal, panel. Use CSS variables for dark/light mode in `public/css/style.css`.

## Micro-Interactions (Exact Snippets)
```css
.btn-glass:hover { transform:scale(1.02) translateY(-1px); filter:brightness(1.08); }
.btn-glass:active { transform:scale(0.97); opacity:.9; }
.card-glass:hover > * { transform:translateY(-2px); transition-delay:.2s; }
header.scrolled { backdrop-filter:blur(32px); background:rgba(255,255,255,.12); }
```

## Lenis Setup (`public/js/lenis-setup.js`)
```js
import Lenis from '@studio-freight/lenis';
const lenis = new Lenis({duration:1.2,easing:t=>Math.min(1,1.001-Math.pow(2,-10*t)),smooth:true});
function raf(time){lenis.raf(time);requestAnimationFrame(raf);} requestAnimationFrame(raf);
lenis.on('scroll',()=>{AOS.refresh();/*GSAP update*/});
```

## GSAP Master Timeline + ScrollTrigger (`public/js/gsap-master.js`)
```js
const masterTL = gsap.timeline();
masterTL.to(".hero-glass",{y:0,opacity:1,duration:1.2,ease:"power3.out"});
ScrollTrigger.create({trigger:".section",start:"top 80%",onEnter:()=>masterTL.play()});
lenis.on('scroll',()=>ScrollTrigger.update());
```

## AOS Init + Triggers (`public/js/aos-setup.js`)
```js
AOS.init({duration:800,easing:'ease-out-cubic',once:true,offset:120});
```
- Hero: `data-aos="fade-down" data-aos-delay="100"`  
- Cards: `data-aos="zoom-in" data-aos-duration="600"`  
- Staggered: `data-aos="fade-up" data-aos-delay="{{index*100}}"`

## Framer-Motion Equivalents (`public/js/framer-equivalents.js`)
- `motion.div` → GSAP timeline + ScrollTrigger  
- `useAnimate` → `gsap.to()`  
- `useInView` → ScrollTrigger  
- Spring → `gsap.from({ease:"elastic.out(1,0.3)"})`

## TODO.md Rules (Ultra-Granular)
1. **Immediately overwrite** `TODO.md` with:
   ```
   # UI/UX Overhaul – Atomic Code Task List (80+ tasks)
   **AI Instruction**: Execute every task in exact order. Each task = ONE file edit + full code block. No skipping.
   ```
2. Generate **≥80 atomic tasks**. Each must include: task number, exact file path, full code snippet/diff, expected visual result.  
3. Cover: all glass classes on every Blade element, Lenis+GSAP+AOS integration, every micro-interaction, bug fixes, dark/light mode, performance (`will-change`, RAF).

## Execution Rules
- Execute strictly in numbered order.  
- For every task: edit file, insert exact code, save, verify locally.  
- After all tasks: update `TODO.md` with ✅ for each line.  
- Output **only completed code changes** – no extra text.

## Success Criteria
- 100% glassmorphism + Lenis 60fps + synced GSAP/AOS  
- Zero layout bugs  
- `TODO.md` contains only executable code tasks (all completed)

**Start: Read all files → Write full TODO.md → Execute every atomic task in order.**
```