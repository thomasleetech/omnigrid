<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Globe | OmniGrid</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,sans-serif;background:#000;color:#fff;overflow:hidden}
#globe{position:fixed;inset:0;cursor:grab}#globe:active{cursor:grabbing}
.ui{position:fixed;z-index:100;pointer-events:none}.ui>*{pointer-events:auto}
.header{top:0;left:0;right:0;padding:1.25rem 2rem;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(rgba(0,0,0,0.7),transparent);transition:opacity 0.3s}
.logo{font-size:1.4rem;font-weight:700;text-decoration:none;color:#fff}.logo span{color:#6366f1}
.btn{background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);color:#fff;border:1px solid rgba(255,255,255,0.2);padding:0.5rem 1rem;border-radius:25px;cursor:pointer;font-size:0.85rem;display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;transition:0.2s}
.btn:hover{background:rgba(255,255,255,0.2)}
.center-ui{top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;transition:opacity 0.4s}
.center-ui h1{font-size:2.2rem;margin-bottom:0.4rem;text-shadow:0 0 30px rgba(99,102,241,0.5)}
.center-ui p{color:rgba(255,255,255,0.6);margin-bottom:1.5rem}
.spin-btn{background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;padding:0.9rem 2.5rem;border-radius:50px;font-size:1rem;cursor:pointer;box-shadow:0 10px 40px rgba(99,102,241,0.4)}
.spin-btn:hover{transform:scale(1.05)}
.controls{position:fixed;right:1.5rem;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:0.4rem;transition:opacity 0.3s}
.ctrl{width:40px;height:40px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;transition:0.2s}
.ctrl:hover{background:rgba(255,255,255,0.2)}
.hint{position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.3);font-size:0.75rem;transition:opacity 0.3s}
.my-loc{position:fixed;width:14px;height:14px;background:#22c55e;border-radius:50%;box-shadow:0 0 20px #22c55e;pointer-events:none;z-index:50;display:none}
.my-loc::after{content:'';position:absolute;inset:-8px;border:2px solid #22c55e;border-radius:50%;animation:ping 1.5s infinite}
@keyframes ping{0%{transform:scale(1);opacity:1}100%{transform:scale(2);opacity:0}}
.popup{position:fixed;background:rgba(10,10,20,0.95);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:1rem;width:260px;display:none;z-index:200}
.popup.show{display:block}
.popup img{width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:0.6rem;background:#222}
.popup h3{font-size:0.9rem}.popup .tag{color:#6366f1;font-size:0.75rem}
.popup .stats{display:flex;gap:1rem;margin:0.6rem 0;font-size:0.75rem;color:rgba(255,255,255,0.5)}
.popup .btn{width:100%;justify-content:center;margin-top:0.5rem}
body.fs .header,body.fs .center-ui,body.fs .hint{opacity:0;pointer-events:none}
</style>
</head>
<body>
<div id="globe"></div>
<div class="ui header"><a href="./" class="logo">Omni<span>Grid</span></a><a href="./" class="btn"><i class="fa fa-th"></i> Grid</a></div>
<div class="ui center-ui" id="centerUI"><h1>Spin the Globe</h1><p>Drop into a random live feed</p><button class="spin-btn" onclick="spin()"><i class="fa fa-globe"></i> Spin</button></div>
<div class="controls"><div class="ctrl" onclick="zoomIn()"><i class="fa fa-plus"></i></div><div class="ctrl" onclick="zoomOut()"><i class="fa fa-minus"></i></div><div class="ctrl" onclick="reset()"><i class="fa fa-home"></i></div><div class="ctrl" onclick="toggleFS()"><i class="fa fa-expand"></i></div></div>
<div class="hint">Drag to rotate · Scroll to zoom · Click to expand</div>
<div class="my-loc" id="myLoc"></div>
<div class="popup" id="popup"><img id="pImg"><h3 id="pTitle"></h3><div class="tag" id="pTag"></div><div class="stats"><span><i class="fa fa-eye"></i> <span id="pViews"></span></span><span><i class="fa fa-users"></i> <span id="pSubs"></span></span></div><a class="btn" id="pLink"><i class="fa fa-play"></i> Watch</a></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
const streams=[{id:1,title:'Shibuya Crossing',tag:'city · ambient',lat:35.66,lng:139.70,views:5150,subs:38},{id:2,title:'Studio Chill',tag:'lofi · cozy',lat:34.05,lng:-118.24,views:3120,subs:21},{id:3,title:'NYC Live',tag:'urban',lat:40.71,lng:-74.00,views:8900,subs:104}];
let scene,camera,renderer,globe,markers=[],isDrag=false,prev={x:0,y:0},vel={x:0,y:0},spinEnd=0,tgtZoom=2.5,zoom=2.5,myLat=null,myLng=null;

function init(){
    scene=new THREE.Scene();
    camera=new THREE.PerspectiveCamera(45,innerWidth/innerHeight,0.1,1000);
    camera.position.z=zoom;
    renderer=new THREE.WebGLRenderer({antialias:true,alpha:true});
    renderer.setSize(innerWidth,innerHeight);
    renderer.setPixelRatio(Math.min(devicePixelRatio,2));
    document.getElementById('globe').appendChild(renderer.domElement);
    
    // Earth with texture
    const geo=new THREE.SphereGeometry(1,64,64);
    const tex=new THREE.CanvasTexture(createTex());
    globe=new THREE.Mesh(geo,new THREE.MeshPhongMaterial({map:tex,bumpScale:0.02,specular:0x333333,shininess:5}));
    scene.add(globe);
    
    // Atmosphere
    const atmosMat=new THREE.ShaderMaterial({vertexShader:`varying vec3 vN;void main(){vN=normalize(normalMatrix*normal);gl_Position=projectionMatrix*modelViewMatrix*vec4(position,1.);}`,fragmentShader:`varying vec3 vN;void main(){float i=pow(0.65-dot(vN,vec3(0,0,1)),2.);gl_FragColor=vec4(0.3,0.6,1.,1.)*i;}`,blending:THREE.AdditiveBlending,side:THREE.BackSide,transparent:true});
    scene.add(new THREE.Mesh(new THREE.SphereGeometry(1.015,64,64),atmosMat));
    
    // Lights
    const sun=new THREE.DirectionalLight(0xffffff,1);sun.position.set(5,3,5);scene.add(sun);
    scene.add(new THREE.AmbientLight(0x334466,0.6));
    
    // Stars
    const starGeo=new THREE.BufferGeometry(),sv=[];
    for(let i=0;i<8000;i++)sv.push((Math.random()-0.5)*200,(Math.random()-0.5)*200,(Math.random()-0.5)*200);
    starGeo.setAttribute('position',new THREE.Float32BufferAttribute(sv,3));
    scene.add(new THREE.Points(starGeo,new THREE.PointsMaterial({color:0xffffff,size:0.05})));
    
    // Markers
    streams.forEach(s=>{
        const p=ll2v(s.lat,s.lng,1.012);
        const m=new THREE.Mesh(new THREE.SphereGeometry(0.018,16,16),new THREE.MeshBasicMaterial({color:0x6366f1}));
        m.position.copy(p);m.userData=s;globe.add(m);markers.push(m);
        const g=new THREE.Mesh(new THREE.SphereGeometry(0.03,16,16),new THREE.MeshBasicMaterial({color:0x6366f1,transparent:true,opacity:0.3}));
        g.position.copy(p);globe.add(g);
    });
    
    getLocation();
    bindEvents();
    animate();
}

function createTex(){
    const c=document.createElement('canvas');c.width=1024;c.height=512;const x=c.getContext('2d');
    x.fillStyle='#0f2942';x.fillRect(0,0,1024,512);
    x.fillStyle='#1a4d3a';
    [[180,140,110,90],[260,320,55,110],[520,220,70,160],[720,140,160,110],[830,340,55,45]].forEach(([cx,cy,rx,ry])=>{x.beginPath();x.ellipse(cx,cy,rx,ry,0,0,Math.PI*2);x.fill();});
    return c;
}

function ll2v(lat,lng,r){
    const phi=(90-lat)*Math.PI/180,theta=(lng+180)*Math.PI/180;
    return new THREE.Vector3(-r*Math.sin(phi)*Math.cos(theta),r*Math.cos(phi),r*Math.sin(phi)*Math.sin(theta));
}

function getLocation(){if(navigator.geolocation)navigator.geolocation.getCurrentPosition(p=>{myLat=p.coords.latitude;myLng=p.coords.longitude;});}

function updateMyLoc(){
    if(myLat===null)return;
    const p=ll2v(myLat,myLng,1.02).applyMatrix4(globe.matrixWorld).project(camera);
    const el=document.getElementById('myLoc');
    if(p.z<1){el.style.display='block';el.style.left=(p.x+1)/2*innerWidth+'px';el.style.top=(-p.y+1)/2*innerHeight+'px';}else el.style.display='none';
}

function bindEvents(){
    const c=renderer.domElement;
    c.addEventListener('mousedown',e=>{isDrag=true;prev={x:e.clientX,y:e.clientY};vel={x:0,y:0};spinEnd=0;});
    c.addEventListener('mousemove',e=>{if(!isDrag)return;const dx=e.clientX-prev.x,dy=e.clientY-prev.y;vel={x:dx*0.002,y:dy*0.002};globe.rotation.y+=dx*0.005;globe.rotation.x=Math.max(-1.5,Math.min(1.5,globe.rotation.x+dy*0.005));prev={x:e.clientX,y:e.clientY};});
    c.addEventListener('mouseup',e=>{if(!isDrag)return;isDrag=false;const spd=Math.sqrt(vel.x*vel.x+vel.y*vel.y);if(spd>0.003){spinEnd=Date.now()+Math.min(17000,spd*50000);vel.x*=5;}});
    c.addEventListener('wheel',e=>{e.preventDefault();tgtZoom=Math.max(1.3,Math.min(5,tgtZoom+e.deltaY*0.001));},{passive:false});
    c.addEventListener('click',e=>{
        const ray=new THREE.Raycaster(),mouse=new THREE.Vector2((e.clientX/innerWidth)*2-1,-(e.clientY/innerHeight)*2+1);
        ray.setFromCamera(mouse,camera);
        const hits=ray.intersectObjects(markers);
        if(hits.length){showPopup(hits[0].object.userData,e.clientX,e.clientY);}else{hidePopup();document.body.classList.toggle('fs');}
    });
    c.addEventListener('touchstart',e=>{e.preventDefault();isDrag=true;prev={x:e.touches[0].clientX,y:e.touches[0].clientY};spinEnd=0;},{passive:false});
    c.addEventListener('touchmove',e=>{e.preventDefault();if(!isDrag)return;const dx=e.touches[0].clientX-prev.x,dy=e.touches[0].clientY-prev.y;vel={x:dx*0.002,y:dy*0.002};globe.rotation.y+=dx*0.005;globe.rotation.x=Math.max(-1.5,Math.min(1.5,globe.rotation.x+dy*0.005));prev={x:e.touches[0].clientX,y:e.touches[0].clientY};},{passive:false});
    c.addEventListener('touchend',()=>{isDrag=false;const spd=Math.sqrt(vel.x*vel.x+vel.y*vel.y);if(spd>0.002){spinEnd=Date.now()+Math.min(17000,spd*80000);vel.x*=8;}});
    addEventListener('resize',()=>{camera.aspect=innerWidth/innerHeight;camera.updateProjectionMatrix();renderer.setSize(innerWidth,innerHeight);});
}

function showPopup(s,x,y){
    const p=document.getElementById('popup');
    p.style.left=Math.min(x+10,innerWidth-280)+'px';p.style.top=Math.min(y+10,innerHeight-220)+'px';
    document.getElementById('pImg').src='assets/img/thumbs/'+s.id+'.jpg';
    document.getElementById('pTitle').textContent=s.title;
    document.getElementById('pTag').textContent=s.tag;
    document.getElementById('pViews').textContent=s.views.toLocaleString();
    document.getElementById('pSubs').textContent=s.subs;
    document.getElementById('pLink').href='live.php?id='+s.id;
    p.classList.add('show');
}
function hidePopup(){document.getElementById('popup').classList.remove('show');}

function spin(){
    document.getElementById('centerUI').style.opacity='0';
    const dur=3+Math.random()*14,dir=Math.random()>0.5?1:-1;
    vel.x=dir*(0.03+Math.random()*0.07);
    spinEnd=Date.now()+dur*1000;
    setTimeout(()=>{
        const s=streams[Math.floor(Math.random()*streams.length)];
        focusOn(s.lat,s.lng);
        setTimeout(()=>showPopup(s,innerWidth/2,innerHeight/2),800);
    },dur*1000);
}

function focusOn(lat,lng){
    const phi=(90-lat)*Math.PI/180,theta=(lng+180)*Math.PI/180;
    globe.rotation.x=Math.PI/2-phi;globe.rotation.y=-theta+Math.PI;
}

function zoomIn(){tgtZoom=Math.max(1.3,tgtZoom-0.4);}
function zoomOut(){tgtZoom=Math.min(5,tgtZoom+0.4);}
function reset(){tgtZoom=2.5;globe.rotation.x=0;globe.rotation.y=0;vel={x:0,y:0};spinEnd=0;document.getElementById('centerUI').style.opacity='1';hidePopup();document.body.classList.remove('fs');}
function toggleFS(){document.body.classList.toggle('fs');if(document.body.classList.contains('fs'))document.documentElement.requestFullscreen?.();else document.exitFullscreen?.();}

function animate(){
    requestAnimationFrame(animate);
    if(Date.now()<spinEnd){const t=1-(spinEnd-Date.now())/(spinEnd-Date.now()+1000);globe.rotation.y+=vel.x*(1-t*t);}
    else if(!isDrag&&Math.abs(vel.x)>0.0001){globe.rotation.y+=vel.x;vel.x*=0.96;}
    zoom+=(tgtZoom-zoom)*0.1;camera.position.z=zoom;
    updateMyLoc();
    renderer.render(scene,camera);
}
init();
</script>
</body>
</html>
