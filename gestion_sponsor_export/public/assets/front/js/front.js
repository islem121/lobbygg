/* Lobby.gg Frontoffice interactions (vanilla JS, no npm) */
(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // ---------- Profile dropdown ----------
  const userMenu = document.querySelector("[data-user-menu]");
  const userDropdown = document.querySelector("[data-user-dropdown]");
  if (userMenu && userDropdown) {
    const toggle = (open) => {
      userDropdown.classList.toggle("open", open);
      userDropdown.setAttribute("aria-hidden", open ? "false" : "true");
    };
    userMenu.addEventListener("click", (e) => {
      e.stopPropagation();
      toggle(!userDropdown.classList.contains("open"));
    });
    document.addEventListener("click", (e) => {
      if (!e.target.closest("[data-user-dropdown]")) toggle(false);
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") toggle(false);
    });
  }

  // ---------- Bottom action bar navigation ----------
  const bottomBtns = $$("[data-bottom-nav]");
  bottomBtns.forEach((btn) => {
    btn.addEventListener("mouseenter", () => {
      btn.style.transform = "translateY(-4px) scale(1.02)";
    });
    btn.addEventListener("mouseleave", () => {
      btn.style.transform = "";
    });
    btn.addEventListener("click", (e) => {
      // Optional: Add ripple effect on click
      const ripple = document.createElement("span");
      ripple.style.cssText = `
        position: absolute;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,107,107,.3);
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
        left: 50%; top: 50%;
        margin-left: -100px; margin-top: -100px;
      `;
      btn.style.position = "relative";
      btn.style.overflow = "hidden";
      btn.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // ---------- Custom cursor (smooth follow) ----------
  const cursor = $("#lobby-cursor");
  const dot = $("#lobby-cursor-dot");
  if (cursor && dot && window.matchMedia("(pointer:fine)").matches) {
    let mx = window.innerWidth / 2, my = window.innerHeight / 2;
    let cx = mx, cy = my, dx = mx, dy = my;

    const lerp = (a, b, t) => a + (b - a) * t;
    window.addEventListener("mousemove", (e) => {
      mx = e.clientX;
      my = e.clientY;
    }, { passive: true });

    const tick = () => {
      cx = lerp(cx, mx, 0.14);
      cy = lerp(cy, my, 0.14);
      dx = lerp(dx, mx, 0.35);
      dy = lerp(dy, my, 0.35);
      cursor.style.left = cx + "px";
      cursor.style.top = cy + "px";
      dot.style.left = dx + "px";
      dot.style.top = dy + "px";
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    const hoverables = "a, button, input, .chip, .card";
    $$(hoverables).forEach((el) => {
      el.addEventListener("mouseenter", () => {
        cursor.style.width = "56px";
        cursor.style.height = "56px";
        cursor.style.borderColor = "#FF6B6B";
      });
      el.addEventListener("mouseleave", () => {
        cursor.style.width = "44px";
        cursor.style.height = "44px";
        cursor.style.borderColor = "#634F9C";
      });
    });
  }

  // ---------- Parallax floating objects ----------
  const layer = $(".lobby-parallax-layer");
  if (layer && window.matchMedia("(pointer:fine)").matches) {
    const objs = $$(".float-obj", layer);
    window.addEventListener("mousemove", (e) => {
      const nx = (e.clientX / window.innerWidth) * 2 - 1;
      const ny = (e.clientY / window.innerHeight) * 2 - 1;
      objs.forEach((o, i) => {
        const p = 8 + i * 6;
        o.style.transform = `translate3d(${nx * p}px, ${ny * p}px, 0)`;
      });
    }, { passive: true });
  }

  // ---------- Particles canvas background ----------
  const canvas = $("#lobby-particles");
  if (canvas) {
    const ctx = canvas.getContext("2d");
    const DPR = Math.min(2, window.devicePixelRatio || 1);
    let w = 0, h = 0;
    let particles = [];

    const resize = () => {
      w = canvas.clientWidth;
      h = canvas.clientHeight;
      canvas.width = Math.floor(w * DPR);
      canvas.height = Math.floor(h * DPR);
      ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
      const count = Math.floor((w * h) / 42000);
        particles = new Array(Math.max(55, Math.min(140, count))).fill(0).map(() => ({
        x: Math.random() * w,
        y: Math.random() * h,
        r: 1.2 + Math.random() * 2.6,
        vx: (-0.15 + Math.random() * 0.3),
        vy: (-0.08 + Math.random() * 0.16),
        a: 0.25 + Math.random() * 0.55,
          // violet / pink / blue + accent
          hue: (() => {
            const r = Math.random();
            if (r < 0.55) return 262;   // violet
            if (r < 0.78) return 318;   // pink
            if (r < 0.93) return 205;   // blue
            return 8;                   // accent red
          })(),
      }));
    };
    resize();
    window.addEventListener("resize", resize, { passive: true });

    const draw = () => {
      ctx.clearRect(0, 0, w, h);
      // soft vignette
      const g = ctx.createRadialGradient(w * 0.5, h * 0.4, 40, w * 0.5, h * 0.4, Math.max(w, h));
      g.addColorStop(0, "rgba(255,255,255,0.02)");
      g.addColorStop(1, "rgba(0,0,0,0.28)");
      ctx.fillStyle = g;
      ctx.fillRect(0, 0, w, h);

      for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < -20) p.x = w + 20;
        if (p.x > w + 20) p.x = -20;
        if (p.y < -20) p.y = h + 20;
        if (p.y > h + 20) p.y = -20;

        ctx.beginPath();
        ctx.fillStyle = `hsla(${p.hue}, 95%, 70%, ${p.a})`;
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      }

      // faint connections
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const a = particles[i], b = particles[j];
          const dx = a.x - b.x, dy = a.y - b.y;
          const d = Math.sqrt(dx * dx + dy * dy);
          if (d < 140) {
            ctx.strokeStyle = `rgba(176,166,214,${(1 - d / 140) * 0.08})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.stroke();
          }
        }
      }

      requestAnimationFrame(draw);
    };
    requestAnimationFrame(draw);
  }

  // ---------- Notification micro explosion ----------
  const notif = $("[data-notif-trigger]");
  if (notif) {
    notif.addEventListener("click", (e) => {
      const rect = notif.getBoundingClientRect();
      const ox = rect.left + rect.width * 0.6;
      const oy = rect.top + rect.height * 0.35;
      const burst = 16 + Math.floor(Math.random() * 10);
      for (let i = 0; i < burst; i++) {
        const p = document.createElement("span");
        p.className = "micro-particle";
        const ang = (Math.PI * 2) * (i / burst);
        const mag = 20 + Math.random() * 46;
        p.style.left = ox + "px";
        p.style.top = oy + "px";
        p.style.setProperty("--dx", `${Math.cos(ang) * mag}px`);
        p.style.setProperty("--dy", `${Math.sin(ang) * mag}px`);
        document.body.appendChild(p);
        setTimeout(() => p.remove(), 650);
      }

      // decrement counter for realism
      const c = $("[data-notif-count]");
      if (c) {
        const n = Math.max(0, (parseInt(c.textContent || "0", 10) || 0) - 1);
        c.textContent = String(n);
      }
    });
  }

  // ---------- Optional GSAP micro-animations (loaded via CDN in base) ----------
  if (window.gsap) {
    const logo = document.querySelector(".lobby-logo");
    const glow = document.querySelector(".logo-glow");
    if (glow) {
      window.gsap.to(glow, { opacity: 1, duration: 1.2, yoyo: true, repeat: -1, ease: "sine.inOut" });
    }
    if (logo) {
      window.gsap.fromTo(logo, { filter: "drop-shadow(0 0 0 rgba(255,107,107,0))" }, {
        filter: "drop-shadow(0 0 18px rgba(255,107,107,.22))",
        duration: 1.6,
        yoyo: true,
        repeat: -1,
        ease: "sine.inOut",
      });
    }
  }

  // ---------- Search: lightweight fake results ----------
  const search = $("[data-search] input");
  const results = $("[data-search-results]");
  if (search && results) {
    const items = [
      { kind: "User", label: "NeonViper" },
      { kind: "User", label: "ArcadeNova" },
      { kind: "Tournoi", label: "Violet Rift Cup" },
      { kind: "Tournoi", label: "Lobby.gg Arena" },
      { kind: "Market", label: "ARGB Keycaps" },
      { kind: "Market", label: "Pro Controller" },
    ];

    const render = (q) => {
      const query = q.trim().toLowerCase();
      if (!query) {
        results.classList.remove("open");
        results.innerHTML = "";
        return;
      }
      const out = items
        .filter((it) => (it.kind + " " + it.label).toLowerCase().includes(query))
        .slice(0, 6);
      results.innerHTML = out.map((it) => `
        <div class="search-item" role="button" tabindex="0">
          <span class="kind">${it.kind}</span>
          <span class="label">${it.label}</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </div>
      `).join("");
      results.classList.toggle("open", out.length > 0);
    };

    search.addEventListener("input", (e) => render(e.target.value));
    document.addEventListener("click", (e) => {
      if (!e.target.closest("[data-search]")) {
        results.classList.remove("open");
      }
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "/" && document.activeElement !== search) {
        e.preventDefault();
        search.focus();
      }
      if (e.key === "Escape") {
        results.classList.remove("open");
        search.blur();
      }
    });
  }

  // ---------- Card tilt + micro bounce ----------
  const tiltCards = $$("[data-tilt]");
  if (tiltCards.length && window.matchMedia("(pointer:fine)").matches) {
    tiltCards.forEach((card) => {
      let raf = 0;
      const onMove = (e) => {
        const r = card.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;
        const py = (e.clientY - r.top) / r.height;
        const rx = (py - 0.5) * -7;
        const ry = (px - 0.5) * 9;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
          card.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg) translateY(-2px) scale(1.01)`;
        });
      };
      const reset = () => {
        cancelAnimationFrame(raf);
        card.style.transform = "";
      };
      card.addEventListener("mousemove", onMove);
      card.addEventListener("mouseleave", reset);
    });
  }

  // ---------- Reactions animation (spark burst on buttons) ----------
  const reactBtns = $$("[data-reaction]");
  reactBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const r = btn.getBoundingClientRect();
      const ox = r.left + r.width * 0.5;
      const oy = r.top + r.height * 0.5;
      const burst = 10;
      for (let i = 0; i < burst; i++) {
        const p = document.createElement("span");
        p.className = "micro-particle";
        p.style.background = (i % 2 === 0) ? "#FF6B6B" : "#634F9C";
        const ang = (Math.PI * 2) * (i / burst);
        const mag = 14 + Math.random() * 28;
        p.style.left = ox + "px";
        p.style.top = oy + "px";
        p.style.setProperty("--dx", `${Math.cos(ang) * mag}px`);
        p.style.setProperty("--dy", `${Math.sin(ang) * mag}px`);
        document.body.appendChild(p);
        setTimeout(() => p.remove(), 650);
      }
      btn.animate([
        { transform: "translateY(0) scale(1)" },
        { transform: "translateY(-2px) scale(1.04)" },
        { transform: "translateY(0) scale(1)" },
      ], { duration: 360, easing: "cubic-bezier(.2,.9,.2,1)" });
    });
  });

  // ---------- Infinite marketplace carousel (RAF translate loop) ----------
  const carousels = $$("[data-infinite-carousel]");
  carousels.forEach((wrap) => {
    const track = $(".market-track", wrap);
    if (!track) return;

    // duplicate children for seamless loop
    const children = Array.from(track.children);
    children.forEach((c) => track.appendChild(c.cloneNode(true)));

    let x = 0;
    let speed = 0.55;
    let paused = false;

    const pauseBtn = $("[data-carousel-pause]");
    if (pauseBtn) {
      pauseBtn.addEventListener("click", () => {
        paused = !paused;
        pauseBtn.innerHTML = paused
          ? '<i class="fa-solid fa-play"></i> Play'
          : '<i class="fa-solid fa-pause"></i> Pause';
      });
    }

    wrap.addEventListener("mouseenter", () => (speed = 0.25));
    wrap.addEventListener("mouseleave", () => (speed = 0.55));

    const step = () => {
      if (!paused) x -= speed;
      const half = track.scrollWidth / 2;
      if (Math.abs(x) >= half) x = 0;
      track.style.transform = `translate3d(${x}px,0,0)`;
      requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  });

  // ---------- Countdown timers for tournaments ----------
  const pads = (n) => String(n).padStart(2, "0");
  const formatHMS = (s) => {
    const sec = Math.max(0, Math.floor(s));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const ss = sec % 60;
    return `${pads(h)}:${pads(m)}:${pads(ss)}`;
  };
  const countdowns = $$("[data-countdown]");
  if (countdowns.length) {
    const tick = () => {
      const now = Math.floor(Date.now() / 1000);
      countdowns.forEach((el) => {
        const end = parseInt(el.getAttribute("data-end") || "0", 10);
        const out = $("[data-countdown-value]", el);
        if (!out) return;
        out.textContent = formatHMS(end - now);
      });
    };
    tick();
    setInterval(tick, 1000);
  }

  // ---------- Sponsor table filter ----------
  const sponsorTables = $$("[data-sponsor-table]");
  sponsorTables.forEach((root) => {
    const filters = $$("[data-tier]", root);
    const rows = $$("[data-tier-row]", root);
    filters.forEach((b) => {
      b.addEventListener("click", () => {
        filters.forEach((x) => x.classList.remove("active"));
        b.classList.add("active");
        const tier = b.getAttribute("data-tier");
        rows.forEach((r) => {
          const ok = (tier === "all") || (r.getAttribute("data-tier-row") === tier);
          r.style.display = ok ? "" : "none";
        });
      });
    });
  });

  // ---------- Count-up stats (footer + KPIs) ----------
  const countEls = $$("[data-countup]");
  const io = ("IntersectionObserver" in window)
    ? new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          animateCount(e.target);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.25 })
    : null;

  const animateCount = (el) => {
    const to = parseInt(el.getAttribute("data-to") || el.textContent || "0", 10) || 0;
    const from = 0;
    const dur = 900 + Math.min(900, Math.floor(to / 400));
    const t0 = performance.now();
    const fmt = new Intl.NumberFormat(undefined);
    const ease = (t) => 1 - Math.pow(1 - t, 3);

    const step = (t) => {
      const p = Math.min(1, (t - t0) / dur);
      const v = Math.floor(from + (to - from) * ease(p));
      el.textContent = fmt.format(v);
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };

  countEls.forEach((el) => {
    if (io) io.observe(el);
    else animateCount(el);
  });

  // ---------- Feed shuffle (tiny delight) ----------
  const shuffle = $("[data-feed-shuffle]");
  if (shuffle) {
    shuffle.addEventListener("click", () => {
      const feed = $(".feed");
      if (!feed) return;
      const cards = Array.from(feed.children);
      for (let i = cards.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [cards[i], cards[j]] = [cards[j], cards[i]];
      }
      cards.forEach((c) => feed.appendChild(c));
      shuffle.animate([
        { transform: "translateY(0)" },
        { transform: "translateY(-2px)" },
        { transform: "translateY(0)" },
      ], { duration: 280, easing: "cubic-bezier(.2,.9,.2,1)" });
    });
  }

  // ---------- Infinite scroll feed (append posts on demand) ----------
  const feed = document.querySelector("[data-feed]");
  const loader = document.querySelector("[data-feed-loader]");
  if (feed) {
    const samples = [
      { tag: "Update", user: "LobbyOps", handle: "@lobbyops", text: "Ranked tweaks: better party MMR calibration + faster queue for solo grinders.", reacts: 1820, comments: 140 },
      { tag: "Tournament", user: "RiftRunner", handle: "@riftrunner", text: "Scrims tonight. Looking for 2 flex players. DM your role + region.", reacts: 611, comments: 58 },
      { tag: "Marketplace", user: "MetaSniper", handle: "@metasniper", text: "Found a clean mic arm + cable kit combo. Minimal. No gamer tax.", reacts: 903, comments: 77 },
      { tag: "Sponsoring", user: "HyperNova", handle: "@hypernova", text: "Sponsor spotlight: gear up your team. Apply for the 2026 Partner Program.", reacts: 430, comments: 39 },
      { tag: "Clip", user: "PulseKira", handle: "@pulsekira", text: "New aim drill: 6 minutes/day. Track your reaction time and consistency.", reacts: 1207, comments: 96 },
    ];

    let page = 0;
    let busy = false;

    const postHTML = (p, time) => {
      const initials = (p.user || "GG").slice(0, 2).toUpperCase();
      const safe = (s) => String(s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[c]));
      return `
        <article class="card post-card" data-tilt>
          <div class="post-top">
            <div class="avatar avatar-grad">${safe(initials)}</div>
            <div class="post-meta">
              <div class="post-user">${safe(p.user)} <span class="muted">${safe(p.handle)}</span></div>
              <div class="post-time">${safe(time)} · <span class="pill pill-accent" style="position:static;height:auto;min-width:auto;padding:2px 10px">${safe(p.tag)}</span></div>
            </div>
            <button class="icon-btn" type="button" aria-label="Post menu"><i class="fa-solid fa-ellipsis"></i></button>
          </div>
          <div class="post-body">${safe(p.text)}</div>
          <div class="post-actions">
            <button class="chip chip-like" type="button" data-reaction><i class="fa-solid fa-fire"></i> Like</button>
            <button class="chip" type="button"><i class="fa-regular fa-comment-dots"></i> Comment</button>
            <button class="chip" type="button"><i class="fa-solid fa-share-nodes"></i> Share</button>
            <div class="post-stats">
              <span><i class="fa-solid fa-heart"></i> ${p.reacts}</span>
              <span><i class="fa-solid fa-comment"></i> ${p.comments}</span>
            </div>
          </div>
        </article>
      `;
    };

    const wireNewInteractions = (root) => {
      // Re-bind reactions for new nodes
      const newReact = root.querySelectorAll("[data-reaction]");
      newReact.forEach((btn) => {
        btn.addEventListener("click", () => {
          const r = btn.getBoundingClientRect();
          const ox = r.left + r.width * 0.5;
          const oy = r.top + r.height * 0.5;
          const burst = 10;
          for (let i = 0; i < burst; i++) {
            const p = document.createElement("span");
            p.className = "micro-particle";
            p.style.background = (i % 2 === 0) ? "#FF6B6B" : "#634F9C";
            const ang = (Math.PI * 2) * (i / burst);
            const mag = 14 + Math.random() * 28;
            p.style.left = ox + "px";
            p.style.top = oy + "px";
            p.style.setProperty("--dx", `${Math.cos(ang) * mag}px`);
            p.style.setProperty("--dy", `${Math.sin(ang) * mag}px`);
            document.body.appendChild(p);
            setTimeout(() => p.remove(), 650);
          }
          btn.animate([
            { transform: "translateY(0) scale(1)" },
            { transform: "translateY(-2px) scale(1.04)" },
            { transform: "translateY(0) scale(1)" },
          ], { duration: 360, easing: "cubic-bezier(.2,.9,.2,1)" });
        }, { once: false });
      });

      // Re-bind tilt for new nodes
      if (window.matchMedia("(pointer:fine)").matches) {
        root.querySelectorAll("[data-tilt]").forEach((card) => {
          if (card.__tiltBound) return;
          card.__tiltBound = true;
          let raf = 0;
          const onMove = (e) => {
            const r = card.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width;
            const py = (e.clientY - r.top) / r.height;
            const rx = (py - 0.5) * -7;
            const ry = (px - 0.5) * 9;
            cancelAnimationFrame(raf);
            raf = requestAnimationFrame(() => {
              card.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg) translateY(-2px) scale(1.01)`;
            });
          };
          const reset = () => {
            cancelAnimationFrame(raf);
            card.style.transform = "";
          };
          card.addEventListener("mousemove", onMove);
          card.addEventListener("mouseleave", reset);
        });
      }
    };

    const appendMore = async () => {
      if (busy) return;
      busy = true;
      if (loader) loader.classList.add("show");

      // fake latency for realism
      await new Promise((r) => setTimeout(r, 420));

      const frag = document.createElement("div");
      const time = page === 0 ? "Just now" : `${3 + page * 2}m`;
      const batch = 3;
      for (let i = 0; i < batch; i++) {
        const p = samples[(page * batch + i) % samples.length];
        frag.insertAdjacentHTML("beforeend", postHTML(p, time));
      }

      // insert before loader (which is outside feed), so append to feed itself
      feed.insertAdjacentHTML("beforeend", frag.innerHTML);
      wireNewInteractions(feed);

      page += 1;
      if (loader) loader.classList.remove("show");
      busy = false;
    };

    // initial wire for existing nodes
    wireNewInteractions(document);

    // Observe scroll end
    const sentinel = document.createElement("div");
    sentinel.style.height = "1px";
    feed.appendChild(sentinel);

    if ("IntersectionObserver" in window) {
      const io2 = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) appendMore();
        });
      }, { rootMargin: "900px 0px 900px 0px", threshold: 0.01 });
      io2.observe(sentinel);
    } else {
      window.addEventListener("scroll", () => {
        const nearBottom = (window.innerHeight + window.scrollY) > (document.body.offsetHeight - 900);
        if (nearBottom) appendMore();
      }, { passive: true });
    }
  }
})();


