import * as THREE from '../vendor/three.module.js';

const canvas = document.getElementById('landing-canvas');
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initScene() {
  if (!canvas) return null;
  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
  } catch (error) {
    console.warn('WebGL tidak tersedia untuk scene 3D.', error);
    canvas.style.display = 'none';
    return null;
  }

  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.12;

  const scene = new THREE.Scene();
  scene.fog = new THREE.Fog(0x061b12, 20, 62);

  const camera = new THREE.PerspectiveCamera(48, 1, 0.1, 120);
  camera.position.set(0, 5.4, 15);
  camera.lookAt(0, 1.4, -7);

  const ambient = new THREE.HemisphereLight(0xd9f3dd, 0x07140d, 1.15);
  scene.add(ambient);

  const moon = new THREE.DirectionalLight(0xd7f3e3, 2.1);
  moon.position.set(-9, 19, 8);
  scene.add(moon);

  const rim = new THREE.DirectionalLight(0x8fc2a3, 0.7);
  rim.position.set(12, 8, -16);
  scene.add(rim);

  // ---------------------------------------------------------------------
  // Terrain low-poly dengan ridge pegunungan.
  // ---------------------------------------------------------------------
  const terrainWidth = 100;
  const terrainDepth = 78;
  const segmentsX = 130;
  const segmentsZ = 100;
  const terrainGeometry = new THREE.PlaneGeometry(terrainWidth, terrainDepth, segmentsX, segmentsZ);
  terrainGeometry.rotateX(-Math.PI / 2);

  const position = terrainGeometry.attributes.position;
  const colors = [];
  const low = new THREE.Color(0x081f14);
  const mid = new THREE.Color(0x143426);
  const high = new THREE.Color(0x3b5c4a);
  const peak = new THREE.Color(0x6d8d7a);

  const heightAt = (x, z) => {
    const back = -z;
    let h = -3.05;
    h += 6.6 * Math.exp(-Math.pow((back - 17) / 10, 2)) * (0.5 + 0.5 * Math.sin(x * 0.2 + 1.15));
    h += 4.3 * Math.exp(-Math.pow((back - 30) / 11, 2)) * (0.55 + 0.45 * Math.cos(x * 0.14 - 0.6));
    h += 2.4 * Math.exp(-Math.pow((back - 43) / 10, 2)) * (0.5 + 0.5 * Math.sin(x * 0.1 + 2.3));
    h += 0.22 * Math.sin(x * 0.85) * Math.cos(z * 0.7);
    h += 0.08 * Math.sin(x * 2.2 + z * 1.4);
    const edge = Math.max(0, Math.abs(x) - 36);
    h -= edge * 0.06;
    return h;
  };

  for (let i = 0; i < position.count; i += 1) {
    const x = position.getX(i);
    const z = position.getZ(i);
    const height = heightAt(x, z);
    position.setY(i, height);
    const t = THREE.MathUtils.clamp((height + 3) / 10, 0, 1);
    const color = t < 0.45 ? low.clone().lerp(mid, t / 0.45) : mid.clone().lerp(high, (t - 0.45) / 0.35);
    if (t > 0.8) color.lerp(peak, (t - 0.8) / 0.2);
    colors.push(color.r, color.g, color.b);
  }
  terrainGeometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
  terrainGeometry.computeVertexNormals();

  const terrain = new THREE.Mesh(
    terrainGeometry,
    new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.96, metalness: 0.02, flatShading: true })
  );
  terrain.position.y = 0;
  scene.add(terrain);

  const wire = new THREE.Mesh(
    terrainGeometry,
    new THREE.MeshBasicMaterial({ color: 0x5a846d, wireframe: true, transparent: true, opacity: 0.055 })
  );
  wire.position.y = 0.01;
  scene.add(wire);

  // ---------------------------------------------------------------------
  // Pohon pinus low-poly di sisi depan.
  // ---------------------------------------------------------------------
  const treeCount = 150;
  const coneGeometry = new THREE.ConeGeometry(0.62, 2.4, 5);
  const trunkGeometry = new THREE.CylinderGeometry(0.075, 0.09, 0.8, 4);
  const treeMaterial = new THREE.MeshStandardMaterial({ color: 0x0e2d1e, roughness: 1, flatShading: true });
  const trunkMaterial = new THREE.MeshStandardMaterial({ color: 0x1c241a, roughness: 1 });
  const trees = new THREE.InstancedMesh(coneGeometry, treeMaterial, treeCount);
  const trunks = new THREE.InstancedMesh(trunkGeometry, trunkMaterial, treeCount);
  const dummy = new THREE.Object3D();

  for (let i = 0; i < treeCount; i += 1) {
    const side = Math.random() > 0.5 ? 1 : -1;
    const x = side * (8 + Math.random() * 34);
    const z = -1 + Math.random() * 24;
    const scale = 0.55 + Math.random() * 1.25;
    const y = heightAt(x, z);
    dummy.position.set(x, y + scale * 1.15, z);
    dummy.scale.setScalar(scale);
    dummy.rotation.y = Math.random() * Math.PI;
    dummy.updateMatrix();
    trees.setMatrixAt(i, dummy.matrix);
    dummy.position.set(x, y + scale * 0.32, z);
    dummy.scale.set(scale, scale, scale);
    dummy.updateMatrix();
    trunks.setMatrixAt(i, dummy.matrix);
  }
  scene.add(trees, trunks);

  // ---------------------------------------------------------------------
  // Tenda utama.
  // ---------------------------------------------------------------------
  const campGroup = new THREE.Group();
  const tentX = 2.7;
  const tentZ = 3.4;
  const tentY = heightAt(tentX, tentZ);

  const tentMaterial = new THREE.MeshStandardMaterial({ color: 0x2f5743, roughness: 0.85, flatShading: true });
  const tentDark = new THREE.MeshStandardMaterial({ color: 0x172d22, roughness: 0.9, flatShading: true });
  const tent = new THREE.Mesh(new THREE.ConeGeometry(1.55, 1.45, 4), tentMaterial);
  tent.position.set(tentX, tentY + 0.72, tentZ);
  tent.rotation.y = Math.PI / 4;
  tent.scale.set(1, 1, 1.45);
  campGroup.add(tent);

  const entrance = new THREE.Mesh(new THREE.CircleGeometry(0.5, 3), tentDark);
  entrance.position.set(tentX - 0.15, tentY + 0.5, tentZ + 1.46);
  entrance.rotation.z = Math.PI / 2;
  entrance.rotation.y = 0.08;
  entrance.scale.set(1, 1.75, 1);
  campGroup.add(entrance);

  const poleMaterial = new THREE.MeshStandardMaterial({ color: 0x9fb8a7, roughness: 0.7, metalness: 0.35 });
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.018, 1.7, 5), poleMaterial);
  pole.position.set(tentX, tentY + 0.85, tentZ - 0.75);
  campGroup.add(pole);

  // ---------------------------------------------------------------------
  // Api unggun + cahaya.
  // ---------------------------------------------------------------------
  const fireX = 0.4;
  const fireZ = 2.1;
  const fireY = heightAt(fireX, fireZ);
  const logMaterial = new THREE.MeshStandardMaterial({ color: 0x3a2a1d, roughness: 1 });
  for (let i = 0; i < 4; i += 1) {
    const log = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.09, 0.75, 5), logMaterial);
    log.position.set(fireX, fireY + 0.13, fireZ);
    log.rotation.z = Math.PI / 2;
    log.rotation.y = (i * Math.PI) / 4;
    campGroup.add(log);
  }

  const flameMaterial = new THREE.MeshBasicMaterial({ color: 0xffb84d, transparent: true, opacity: 0.92, blending: THREE.AdditiveBlending });
  const flame = new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.8, 5), flameMaterial);
  flame.position.set(fireX, fireY + 0.52, fireZ);
  campGroup.add(flame);

  const flameInner = new THREE.Mesh(
    new THREE.ConeGeometry(0.1, 0.45, 5),
    new THREE.MeshBasicMaterial({ color: 0xfff1b8, blending: THREE.AdditiveBlending, transparent: true, opacity: 0.95 })
  );
  flameInner.position.set(fireX, fireY + 0.38, fireZ);
  campGroup.add(flameInner);

  const fireLight = new THREE.PointLight(0xff9b44, 18, 14, 2);
  fireLight.position.set(fireX, fireY + 0.8, fireZ);
  campGroup.add(fireLight);

  // Partikel api.
  const emberCount = 60;
  const emberPositions = new Float32Array(emberCount * 3);
  for (let i = 0; i < emberCount; i += 1) {
    emberPositions[i * 3] = fireX + (Math.random() - 0.5) * 0.35;
    emberPositions[i * 3 + 1] = fireY + 0.4 + Math.random() * 1.8;
    emberPositions[i * 3 + 2] = fireZ + (Math.random() - 0.5) * 0.35;
  }
  const emberGeometry = new THREE.BufferGeometry();
  emberGeometry.setAttribute('position', new THREE.BufferAttribute(emberPositions, 3));
  const embers = new THREE.Points(
    emberGeometry,
    new THREE.PointsMaterial({ color: 0xffb24d, size: 0.035, transparent: true, opacity: 0.75, blending: THREE.AdditiveBlending })
  );
  campGroup.add(embers);

  // ---------------------------------------------------------------------
  // Siluet pendaki menghadap pegunungan.
  // ---------------------------------------------------------------------
  const hiker = new THREE.Group();
  const hikerX = -3.15;
  const hikerZ = 3.3;
  const hikerY = heightAt(hikerX, hikerZ);
  const silhouette = new THREE.MeshStandardMaterial({ color: 0x0a1a12, roughness: 1 });
  const packMaterial = new THREE.MeshStandardMaterial({ color: 0x254534, roughness: 1, flatShading: true });

  const body = new THREE.Mesh(new THREE.CapsuleGeometry(0.22, 0.68, 3, 6), silhouette);
  body.position.set(0, 1.02, 0);
  hiker.add(body);
  const head = new THREE.Mesh(new THREE.SphereGeometry(0.17, 7, 6), silhouette);
  head.position.set(0, 1.52, 0);
  hiker.add(head);
  const pack = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.58, 0.26), packMaterial);
  pack.position.set(0, 1.1, -0.24);
  hiker.add(pack);
  for (const side of [-1, 1]) {
    const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.06, 0.6, 5), silhouette);
    leg.position.set(side * 0.11, 0.36, 0);
    leg.rotation.x = side * 0.05;
    hiker.add(leg);
  }
  const stick = new THREE.Mesh(new THREE.CylinderGeometry(0.014, 0.014, 1.35, 4), poleMaterial);
  stick.position.set(0.35, 0.72, 0.1);
  stick.rotation.z = -0.1;
  hiker.add(stick);
  hiker.position.set(hikerX, hikerY, hikerZ);
  hiker.rotation.y = Math.PI + 0.35;
  campGroup.add(hiker);

  // ---------------------------------------------------------------------
  // Pendaki bergerak naik/turun di lereng gunung.
  // ---------------------------------------------------------------------
  const hikerBodyGeo = new THREE.CapsuleGeometry(0.22, 0.56, 3, 6);
  const hikerHeadGeo = new THREE.SphereGeometry(0.16, 7, 6);
  const hikerPackGeo = new THREE.BoxGeometry(0.4, 0.52, 0.24);
  const hikerLegGeo = new THREE.CylinderGeometry(0.065, 0.055, 0.56, 5);
  const hikerArmGeo = new THREE.CylinderGeometry(0.05, 0.045, 0.5, 4);
  const hikerLampGeo = new THREE.SphereGeometry(0.06, 6, 5);
  const hikerPoleGeo = new THREE.CylinderGeometry(0.016, 0.014, 1.0, 5);
  const hikerBedrollGeo = new THREE.CylinderGeometry(0.09, 0.09, 0.42, 6);
  const hikerTorchGeo = new THREE.CylinderGeometry(0.038, 0.046, 0.2, 5);
  const hikerBeamGeo = new THREE.ConeGeometry(0.24, 2.1, 8, 1, true);
  const hikerTorchBeamGeo = new THREE.ConeGeometry(0.16, 1.4, 8, 1, true);
  const hikerDark = new THREE.MeshStandardMaterial({ color: 0x08150f, roughness: 1, flatShading: true });
  const hikerPack = new THREE.MeshStandardMaterial({ color: 0x5b4a33, roughness: 1, flatShading: true });
  const hikerLamp = new THREE.MeshBasicMaterial({ color: 0xffedb0 });
  const hikerPoleMat = new THREE.MeshStandardMaterial({ color: 0xd9c39a, roughness: 0.7 });

  const makeBeam = (geometry, opacity) => {
    const material = new THREE.MeshBasicMaterial({
      color: 0xffe5a6,
      transparent: true,
      opacity,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
      side: THREE.DoubleSide,
    });
    const mesh = new THREE.Mesh(geometry, material);
    mesh.rotation.x = -Math.PI / 2 - 0.06;
    return mesh;
  };

  const makeHiker = (color, scale, poles, torch) => {
    const group = new THREE.Group();
    group.scale.setScalar(scale);
    group.rotation.order = 'YXZ';

    const jacket = new THREE.MeshStandardMaterial({ color, roughness: 0.92, flatShading: true });
    const body = new THREE.Mesh(hikerBodyGeo, jacket);
    body.position.y = 1.02;
    group.add(body);

    const head = new THREE.Mesh(hikerHeadGeo, hikerDark);
    head.position.y = 1.5;
    group.add(head);

    const pack = new THREE.Mesh(hikerPackGeo, hikerPack);
    pack.position.set(0, 1.08, -0.26);
    group.add(pack);

    const bedroll = new THREE.Mesh(hikerBedrollGeo, jacket);
    bedroll.rotation.z = Math.PI / 2;
    bedroll.position.set(0, 1.36, -0.26);
    group.add(bedroll);

    const lamp = new THREE.Mesh(hikerLampGeo, hikerLamp);
    lamp.position.set(0, 1.48, 0.17);
    group.add(lamp);

    const headBeam = makeBeam(hikerBeamGeo, 0.1);
    headBeam.position.set(0, 1.48, 1.1 + 0.17);
    group.add(headBeam);

    const legs = [-1, 1].map((side) => {
      const pivot = new THREE.Group();
      pivot.position.set(side * 0.11, 0.62, 0);
      const leg = new THREE.Mesh(hikerLegGeo, hikerDark);
      leg.position.y = -0.28;
      pivot.add(leg);
      group.add(pivot);
      return pivot;
    });

    const arms = [-1, 1].map((side) => {
      const pivot = new THREE.Group();
      pivot.position.set(side * 0.27, 1.2, 0);
      const arm = new THREE.Mesh(hikerArmGeo, jacket);
      arm.position.y = -0.23;
      pivot.add(arm);
      group.add(pivot);
      return pivot;
    });

    const poleLimit = Math.max(0, Math.min(2, poles));
    arms.forEach((pivot, index) => {
      if (index >= poleLimit) return;
      const poleMesh = new THREE.Mesh(hikerPoleGeo, hikerPoleMat);
      const side = index === 0 ? -1 : 1;
      poleMesh.position.set(side * 0.03, -0.82, 0.06);
      poleMesh.rotation.z = side * 0.06;
      pivot.add(poleMesh);
    });

    let torchBeam = null;
    if (torch) {
      const handTorch = new THREE.Mesh(hikerTorchGeo, hikerDark);
      handTorch.rotation.x = Math.PI / 2;
      handTorch.position.set(0, -0.5, 0.12);
      arms[1].add(handTorch);
      torchBeam = makeBeam(hikerTorchBeamGeo, 0.09);
      torchBeam.rotation.x = -Math.PI / 2 - 0.16;
      torchBeam.position.set(0, -0.5, 0.75 + 0.12);
      arms[1].add(torchBeam);
    }

    group.userData = { legs, arms, headBeam, torchBeam };
    return group;
  };

  const hikerRoutes = [
    { a: [-9.0, -4.0], b: [-5.2, -15.5], speed: 0.038, offset: 0.15, scale: 1.32, color: 0xd9803f, poles: 2, torch: false },
    { a: [-5.2, -15.5], b: [-9.0, -4.0], speed: 0.038, offset: 1.15, scale: 1.32, color: 0xd9803f, poles: 2, torch: false },
    { a: [4.4, -15.0], b: [6.8, -4.5], speed: 0.034, offset: 0.55, scale: 1.24, color: 0x5fbd7c, poles: 1, torch: true },
    { a: [0.8, -4.5], b: [2.6, -16.5], speed: 0.045, offset: 0.9, scale: 1.2, color: 0xd5b04a, poles: 1, torch: false },
    { a: [-9.5, -22.0], b: [-5.5, -30.5], speed: 0.03, offset: 0.35, scale: 1.14, color: 0x6fa7c7, poles: 1, torch: true },
    { a: [6.2, -21.0], b: [3.2, -30.0], speed: 0.034, offset: 1.4, scale: 1.1, color: 0xc97070, poles: 0, torch: false },
  ];

  const climbers = hikerRoutes.map((route) => {
    const group = makeHiker(route.color, route.scale, route.poles, route.torch);
    scene.add(group);
    return {
      ...route,
      group,
      legs: group.userData.legs,
      arms: group.userData.arms,
      headBeam: group.userData.headBeam,
      torchBeam: group.userData.torchBeam,
      ax: route.a[0],
      az: route.a[1],
      bx: route.b[0],
      bz: route.b[1],
    };
  });

  const placeClimbers = (elapsed, moving = true) => {
    climbers.forEach((climber) => {
      const cycle = (elapsed * climber.speed + climber.offset) % 2;
      const phase = cycle <= 1 ? cycle : 2 - cycle;
      const direction = cycle <= 1 ? 1 : -1;
      const t = 0.5 - 0.5 * Math.cos(Math.PI * phase);
      const wobble = Math.sin(phase * Math.PI * 2 + climber.offset * 4) * 0.24;
      const x = THREE.MathUtils.lerp(climber.ax, climber.bx, t) + wobble;
      const z = THREE.MathUtils.lerp(climber.az, climber.bz, t);
      const y = heightAt(x, z);
      const aheadT = THREE.MathUtils.clamp(t + direction * 0.035, 0, 1);
      const aheadX = THREE.MathUtils.lerp(climber.ax, climber.bx, aheadT) + wobble;
      const aheadZ = THREE.MathUtils.lerp(climber.az, climber.bz, aheadT);
      const aheadY = heightAt(aheadX, aheadZ);
      const yaw = Math.atan2(aheadX - x, aheadZ - z);
      const run = Math.hypot(aheadX - x, aheadZ - z) || 0.001;
      const pitch = Math.atan2(aheadY - y, run);
      const stride = moving ? elapsed * 6.2 + climber.offset * 9 : 0.7;
      const bob = Math.abs(Math.sin(stride)) * 0.022 * climber.scale;

      climber.group.position.set(x, y + bob, z);
      climber.group.rotation.y = yaw;
      climber.group.rotation.x = -pitch * 0.65;
      climber.legs[0].rotation.x = Math.sin(stride) * 0.55;
      climber.legs[1].rotation.x = -Math.sin(stride) * 0.55;
      climber.arms[0].rotation.x = -Math.sin(stride) * 0.36;
      climber.arms[1].rotation.x = Math.sin(stride) * 0.36;
      const glow = 0.09 + Math.sin(elapsed * 4.5 + climber.offset * 9) * 0.015;
      if (climber.headBeam) climber.headBeam.material.opacity = glow;
      if (climber.torchBeam) climber.torchBeam.material.opacity = glow * 0.9;
    });
  };

  // ---------------------------------------------------------------------
  // Bintang dan kabut partikel.
  // ---------------------------------------------------------------------
  const starCount = 420;
  const starPositions = new Float32Array(starCount * 3);
  for (let i = 0; i < starCount; i += 1) {
    const radius = 38 + Math.random() * 30;
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.random() * Math.PI * 0.42;
    starPositions[i * 3] = Math.cos(theta) * Math.cos(phi) * radius;
    starPositions[i * 3 + 1] = Math.sin(phi) * radius * 0.7 + 9;
    starPositions[i * 3 + 2] = Math.sin(theta) * Math.cos(phi) * radius - 10;
  }
  const starGeometry = new THREE.BufferGeometry();
  starGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
  const stars = new THREE.Points(
    starGeometry,
    new THREE.PointsMaterial({ color: 0xd8f5e3, size: 0.26, transparent: true, opacity: 0.6, depthWrite: false })
  );
  scene.add(stars);

  const dustCount = 180;
  const dustPositions = new Float32Array(dustCount * 3);
  for (let i = 0; i < dustCount; i += 1) {
    dustPositions[i * 3] = (Math.random() - 0.5) * 34;
    dustPositions[i * 3 + 1] = 0.2 + Math.random() * 7;
    dustPositions[i * 3 + 2] = -6 + Math.random() * 20;
  }
  const dustGeometry = new THREE.BufferGeometry();
  dustGeometry.setAttribute('position', new THREE.BufferAttribute(dustPositions, 3));
  const dust = new THREE.Points(
    dustGeometry,
    new THREE.PointsMaterial({ color: 0x9fd2b4, size: 0.035, transparent: true, opacity: 0.45, depthWrite: false })
  );
  scene.add(dust);

  scene.add(campGroup);

  // ---------------------------------------------------------------------
  // Interaksi & animasi.
  // ---------------------------------------------------------------------
  const pointer = { x: 0, y: 0 };
  const target = { x: 0, y: 0 };
  let frameId = 0;
  let running = true;
  const clock = new THREE.Clock();

  const resize = () => {
    const rect = canvas.parentElement.getBoundingClientRect();
    const width = Math.max(1, rect.width);
    const height = Math.max(1, rect.height);
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.fov = width < 768 ? 58 : 48;
    camera.updateProjectionMatrix();
  };
  resize();
  window.addEventListener('resize', () => window.requestAnimationFrame(resize));

  window.addEventListener('pointermove', (event) => {
    pointer.x = (event.clientX / window.innerWidth - 0.5) * 2;
    pointer.y = (event.clientY / window.innerHeight - 0.5) * 2;
  }, { passive: true });

  document.addEventListener('visibilitychange', () => {
    running = !document.hidden;
    if (running && !reduceMotion) animate();
  });

  const animate = () => {
    if (!running) return;
    frameId = window.requestAnimationFrame(animate);
    const elapsed = clock.getElapsedTime();

    target.x += (pointer.x * 0.55 - target.x) * 0.03;
    target.y += (-pointer.y * 0.25 - target.y) * 0.03;

    camera.position.x = target.x + Math.sin(elapsed * 0.08) * 0.35;
    camera.position.y = 5.4 + target.y + Math.sin(elapsed * 0.12) * 0.08;
    camera.position.z = 15 + Math.cos(elapsed * 0.06) * 0.12;
    camera.lookAt(target.x * 0.4, 1.5, -7);

    const flicker = 0.82 + Math.sin(elapsed * 13.5) * 0.08 + Math.sin(elapsed * 23.7) * 0.05;
    fireLight.intensity = 16 * flicker;
    flame.scale.y = 1 + Math.sin(elapsed * 9) * 0.14;
    flameInner.scale.y = 1 + Math.cos(elapsed * 12) * 0.16;
    flameMaterial.opacity = 0.82 + Math.sin(elapsed * 15) * 0.1;

    const emberArray = emberGeometry.attributes.position.array;
    for (let i = 0; i < emberCount; i += 1) {
      emberArray[i * 3 + 1] += 0.004 + (i % 5) * 0.0008;
      if (emberArray[i * 3 + 1] > fireY + 2.6) {
        emberArray[i * 3 + 1] = fireY + 0.35;
      }
    }
    emberGeometry.attributes.position.needsUpdate = true;

    dust.rotation.y = elapsed * 0.01;
    stars.rotation.y = elapsed * 0.003;
    placeClimbers(elapsed, true);
    renderer.render(scene, camera);
  };

  if (reduceMotion) {
    placeClimbers(1.2, false);
    renderer.render(scene, camera);
  } else {
    animate();
  }

  window.addEventListener('pagehide', () => {
    window.cancelAnimationFrame(frameId);
    running = false;
    renderer.dispose();
  }, { once: true });

  return { scene, camera, renderer };
}

initScene();

// Ambient sound hutan/bukit dengan Web Audio, hanya setelah interaksi pengguna.
function initSound() {
  const button = document.getElementById('sound-toggle');
  if (!button) return;
  let audioContext = null;
  let gain = null;
  let enabled = false;

  const createNoise = () => {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return false;
    audioContext = new AudioCtx();
    const seconds = 4;
    const buffer = audioContext.createBuffer(1, audioContext.sampleRate * seconds, audioContext.sampleRate);
    const data = buffer.getChannelData(0);
    let last = 0;
    for (let i = 0; i < data.length; i += 1) {
      const white = Math.random() * 2 - 1;
      last = (last + 0.015 * white) / 1.015;
      data[i] = last * 3.2;
    }
    const source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.loop = true;
    const filter = audioContext.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = 620;
    filter.Q.value = 0.6;
    gain = audioContext.createGain();
    gain.gain.value = 0;
    source.connect(filter).connect(gain).connect(audioContext.destination);
    source.start();
    return true;
  };

  button.addEventListener('click', async () => {
    if (!audioContext && !createNoise()) {
      button.disabled = true;
      return;
    }
    if (audioContext.state === 'suspended') await audioContext.resume();
    enabled = !enabled;
    const now = audioContext.currentTime;
    gain.gain.cancelScheduledValues(now);
    gain.gain.setTargetAtTime(enabled ? 0.055 : 0, now, enabled ? 0.6 : 0.3);
    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    button.setAttribute('aria-label', enabled ? 'Matikan suara ambience' : 'Nyalakan suara ambience');
    button.querySelector('[data-sound-on]')?.classList.toggle('hidden', !enabled);
    button.querySelector('[data-sound-off]')?.classList.toggle('hidden', enabled);
    if (enabled) window.RENTIFY?.toast('Suara alam diaktifkan. Nikmati suasana bukit.', 'info', 2600);
  });
}

initSound();
