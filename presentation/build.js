// IronForge pitch deck (PowerPoint), presented by MDDInfotech.
const pptxgen = require("pptxgenjs");
const sharp = require("sharp");
const React = require("react");
const ReactDOMServer = require("react-dom/server");
const lu = require("react-icons/lu");
const path = require("path");
const { applyTheme } = require(process.env.SKILL_DIR + "/scripts/apply_theme.js");

const OUT = process.argv[2] || "IronForge-Pitch.pptx";
const LOGO = process.env.LOGO || ""; // optional company logo file

// ---------- theme ----------
const HEX = {
  ink950: "07090C", ink900: "0C1015", ink850: "11161D", ink800: "161C25",
  ink700: "222A36", ink600: "323C4B", volt: "D4FF3F", white: "F4F7F2",
  slate: "94A3B8", slate300: "CBD5E1", sky: "0EA5E9",
};
const THEME = {
  name: "IronForge",
  headFontFace: "Arial",
  bodyFontFace: "Calibri",
  colors: {
    dk1: HEX.ink950, lt1: HEX.white, dk2: HEX.ink900, lt2: HEX.slate300,
    accent1: HEX.volt, accent2: HEX.slate, accent3: HEX.ink600, accent4: HEX.ink700,
    accent5: HEX.ink800, accent6: HEX.ink850, hlink: HEX.sky, folHlink: HEX.slate,
  },
};

const pres = new pptxgen();
pres.layout = "LAYOUT_WIDE"; // 13.333 x 7.5
pres.theme = { headFontFace: THEME.headFontFace, bodyFontFace: THEME.bodyFontFace };
pres.author = "MDDInfotech";
pres.company = "MDDInfotech";
pres.title = "IronForge: Gym Management and Body Transformation Platform";
const C = pres.SchemeColor;
const K = {
  ink950: C.text1, ink900: C.text2, ink850: C.accent6, ink800: C.accent5,
  ink700: C.accent4, ink600: C.accent3, volt: C.accent1, white: C.background1,
  slate: C.accent2, slate300: C.background2,
};

const X0 = 0.6, W = 12.133, TOP = 1.95;

// ---------- images ----------
async function icon(name, color, px = 256) {
  const Comp = lu[name];
  if (!Comp) throw new Error("missing icon " + name);
  const svg = ReactDOMServer.renderToStaticMarkup(
    React.createElement(Comp, { color: "#" + color, size: px, strokeWidth: 2 })
  );
  const buf = await sharp(Buffer.from(svg)).png().toBuffer();
  return "image/png;base64," + buf.toString("base64");
}
async function backdrop(flip) {
  let lines = "";
  for (let x = 0; x <= 1920; x += 72) lines += `<rect x="${x}" y="0" width="2" height="1080" fill="#ffffff" fill-opacity="0.035"/>`;
  for (let y = 0; y <= 1080; y += 72) lines += `<rect x="0" y="${y}" width="1920" height="2" fill="#ffffff" fill-opacity="0.035"/>`;
  const a = flip ? [1500, 80] : [250, 120];
  const b = flip ? [250, 1000] : [1650, 950];
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080">
  <defs>
   <radialGradient id="g1" cx="${a[0]}" cy="${a[1]}" r="700" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#D4FF3F" stop-opacity="0.20"/><stop offset="1" stop-color="#D4FF3F" stop-opacity="0"/></radialGradient>
   <radialGradient id="g2" cx="${b[0]}" cy="${b[1]}" r="650" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#0EA5E9" stop-opacity="0.14"/><stop offset="1" stop-color="#0EA5E9" stop-opacity="0"/></radialGradient>
  </defs>
  <rect width="1920" height="1080" fill="#07090C"/>${lines}
  <rect width="1920" height="1080" fill="url(#g1)"/><rect width="1920" height="1080" fill="url(#g2)"/></svg>`;
  const buf = await sharp(Buffer.from(svg)).png().toBuffer();
  return "image/png;base64," + buf.toString("base64");
}
async function rings() {
  const ring = (r, color, p) => {
    const c = 2 * Math.PI * r;
    return `<circle cx="300" cy="300" r="${r}" fill="none" stroke="#161C25" stroke-width="32"/>
    <circle cx="300" cy="300" r="${r}" fill="none" stroke="${color}" stroke-width="32" stroke-linecap="round"
      stroke-dasharray="${c.toFixed(1)}" stroke-dashoffset="${(c * (1 - p)).toFixed(1)}" transform="rotate(-90 300 300)"/>`;
  };
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600">${ring(270, "#D4FF3F", 0.78)}${ring(205, "#F4F7F2", 0.62)}${ring(140, "#323C4B", 0.86)}</svg>`;
  const buf = await sharp(Buffer.from(svg)).resize(1200, 1200).png().toBuffer();
  return "image/png;base64," + buf.toString("base64");
}

// ---------- helpers ----------
function T(slide, text, o) {
  slide.addText(text, Object.assign({ isTextBox: true, margin: 0, valign: "top", fontFace: undefined }, o));
}
function card(slide, x, y, w, h, o = {}) {
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w, h, rectRadius: o.r ?? 0.14,
    fill: { color: o.fill ?? K.ink850, transparency: o.transparency ?? 0 },
    line: { color: o.line ?? K.ink700, width: o.lineW ?? 1.25, dashType: o.dash ?? "solid", transparency: o.lineT ?? 0 },
    shadow: o.glow ? { type: "outer", color: HEX.volt, opacity: 0.35, blur: 24, offset: 6, angle: 90 } : undefined,
    objectName: o.name,
  });
}
async function tile(slide, name, x, y, s = 0.6, o = {}) {
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w: s, h: s, rectRadius: s * 0.25,
    fill: { color: o.bg ?? K.volt, transparency: o.bgT ?? 88 }, line: { type: "none" },
  });
  const pad = s * 0.24;
  slide.addImage({ data: await icon(name, o.color ?? HEX.volt), x: x + pad, y: y + pad, w: s - 2 * pad, h: s - 2 * pad, altText: o.alt ?? "" });
}
function pill(slide, text, x, y, o = {}) {
  const fs = o.fontSize ?? 12;
  const w = o.w ?? (text.length * fs * 0.0078 + 0.4);
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x, y, w, h: o.h ?? 0.34, rectRadius: (o.h ?? 0.34) / 2,
    fill: { color: o.fill ?? K.volt, transparency: o.fillT ?? 0 },
    line: o.line ? { color: o.line, width: 1 } : { type: "none" },
  });
  T(slide, text, { x, y, w, h: o.h ?? 0.34, fontSize: fs, bold: true, color: o.color ?? K.ink950, align: "center", valign: "middle" });
  return w;
}
function strip(slide, iconData, text, y) {
  card(slide, X0, y, W, 0.85, { fill: K.volt, transparency: 92, line: K.volt, lineT: 55 });
  slide.addImage({ data: iconData, x: X0 + 0.35, y: y + 0.24, w: 0.38, h: 0.38, altText: "" });
  T(slide, text, { x: X0 + 1.0, y, w: W - 1.3, h: 0.85, fontSize: 17, color: K.white, valign: "middle" });
}
function head(slide, eyebrow, title) {
  slide.addText(eyebrow.toUpperCase(), { placeholder: "eyebrow" });
  slide.addText(title, { placeholder: "title" });
}

// ---------- layouts ----------
function contentLayout(title, bg) {
  const objects = [
    { placeholder: { options: { name: "eyebrow", type: "body", x: X0, y: 0.55, w: 9, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, color: K.volt, charSpacing: 4, margin: 0, valign: "top" }, text: "" } },
    { placeholder: { options: { name: "title", type: "title", x: X0, y: 0.88, w: W, h: 0.8, fontFace: "Arial", fontSize: 36, bold: true, color: K.white, margin: 0, valign: "top" }, text: "" } },
    { text: { text: LOGO ? "IronForge  ·" : "IronForge  ·  MDDInfotech", options: { x: X0, y: 7.0, w: LOGO ? 0.85 : 5, h: 0.3, fontSize: 10, color: K.slate, margin: 0, isTextBox: true, valign: "middle" } } },
    ...(LOGO ? [{ image: { path: LOGO, x: X0 + 0.9, y: 7.05, w: 0.2 * (+process.env.LOGO_RATIO || 6), h: 0.2, altText: "MDDInfotech logo" } }] : []),
  ];
  pres.defineSlideMaster({
    title, background: { color: bg }, objects,
    slideNumber: { x: 12.13, y: 7.0, w: 0.6, h: 0.3, fontSize: 10, color: K.slate, align: "right" },
  });
}

(async () => {
  const bgCover = await backdrop(false);
  const bgClose = await backdrop(true);
  const ringImg = await rings();
  const I = {};
  for (const n of ["LuLightbulb", "LuLock", "LuPlay", "LuClock", "LuAlertTriangle", "LuCheck", "LuShieldCheck", "LuUsers"])
    I[n] = await icon(n, HEX.volt);
  const dumbbellDark = await icon("LuDumbbell", HEX.ink950);

  pres.defineSlideMaster({ title: "COVER", background: { data: bgCover } });
  pres.defineSlideMaster({ title: "CLOSING", background: { data: bgClose } });
  contentLayout("CONTENT", K.ink900);
  contentLayout("CONTENT_DEEP", K.ink950);
  pres.defineSlideMaster({
    title: "STATEMENT", background: { color: K.volt },
    objects: [
      { placeholder: { options: { name: "eyebrow", type: "body", x: X0, y: 1.9, w: 10, h: 0.35, fontFace: "Arial", fontSize: 14, bold: true, color: K.ink950, charSpacing: 4, margin: 0 }, text: "" } },
      { placeholder: { options: { name: "title", type: "title", x: X0, y: 2.4, w: 11.8, h: 2.8, fontFace: "Arial", fontSize: 54, bold: true, color: K.ink950, margin: 0, valign: "top" }, text: "" } },
      { placeholder: { options: { name: "body", type: "body", x: X0, y: 5.35, w: 10.5, h: 1.0, fontSize: 24, color: K.ink800, margin: 0, valign: "top" }, text: "" } },
    ],
    slideNumber: { x: 12.13, y: 7.0, w: 0.6, h: 0.3, fontSize: 10, color: K.ink800, align: "right" },
  });

  function brand(slide, x, y) {
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, { x, y, w: 0.62, h: 0.62, rectRadius: 0.16, fill: { color: K.volt }, line: { type: "none" } });
    slide.addImage({ data: dumbbellDark, x: x + 0.13, y: y + 0.13, w: 0.36, h: 0.36, altText: "IronForge logo, a dumbbell" });
    T(slide, "IronForge", { x: x + 0.8, y, w: 3, h: 0.62, fontFace: "Arial", fontSize: 26, bold: true, color: K.white, valign: "middle" });
  }
  function presenter(slide, x, y) {
    if (LOGO) {
      T(slide, "PRESENTED BY", { x, y, w: 3, h: 0.3, fontSize: 11, bold: true, color: K.slate, charSpacing: 3 });
      slide.addImage({ path: LOGO, x, y: y + 0.38, h: 0.5, w: 0.5 * (+process.env.LOGO_RATIO || 6), altText: "MDDInfotech logo" });
    } else {
      T(slide, [
        { text: "Presented by ", options: { color: K.slate } },
        { text: "MDDInfotech", options: { color: K.white, bold: true } },
        { text: "  ·  November 2026", options: { color: K.slate } },
      ], { x, y, w: 8, h: 0.4, fontSize: 16 });
    }
  }

  // ===== 1 COVER =====
  pres.addSection({ title: "The opportunity" });
  let s = pres.addSlide({ masterName: "COVER", sectionTitle: "The opportunity" });
  brand(s, X0, 0.75);
  T(s, "GYM MANAGEMENT + AI BODY TRANSFORMATION", { x: X0, y: 1.85, w: 8, h: 0.35, fontFace: "Arial", fontSize: 14, bold: true, color: K.volt, charSpacing: 4 });
  T(s, [
    { text: "Manage your gym.", options: { breakLine: true } },
    { text: "Track your members.", options: { breakLine: true } },
    { text: "Visualize their transformation.", options: { color: K.volt } },
  ], { x: X0, y: 2.3, w: 8.4, h: 2.75, fontFace: "Arial", fontSize: 40, bold: true, color: K.white, lineSpacingMultiple: 1.0 });
  T(s, "One subscription platform for gyms, fitness centers and personal training studios, launching in Jaipur.",
    { x: X0, y: 5.2, w: 7.4, h: 0.8, fontSize: 18, color: K.slate });
  presenter(s, X0, 6.25);
  s.addImage({ data: ringImg, x: 9.13, y: 1.75, w: 3.6, h: 3.6, altText: "Three progress rings, like a monthly transformation tracker" });
  T(s, "30", { x: 9.13, y: 3.0, w: 3.6, h: 0.75, fontFace: "Arial", fontSize: 48, bold: true, color: K.volt, align: "center", valign: "middle" });
  T(s, "DAY CYCLE", { x: 9.13, y: 3.72, w: 3.6, h: 0.3, fontSize: 11, bold: true, color: K.white, align: "center", charSpacing: 3 });
  s.addNotes("Today we at MDDInfotech want to pitch a new SaaS product, working name IronForge: an AI-powered gym management and body transformation platform. It helps gym owners run their whole business digitally and gives their members a modern way to see their fitness progress. I’ll cover the problem, the product and how it works, the prototype that already runs, how gyms benefit, how we benefit, the revenue model, the sales strategy and the build plan.");

  // ===== 2 PROBLEM =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "The opportunity" });
  head(s, "The problem", "Gyms still run on paper and WhatsApp");
  {
    const items = [
      ["01", "Manual operations", "Members, fees and attendance live in registers, spreadsheets and chat groups."],
      ["02", "Leaking revenue", "Dues and renewals are tracked by hand, so they slip, and owners lose hours to admin."],
      ["03", "No progress record", "Members can’t see how far they’ve come, which makes it harder to stay motivated and renew."],
    ];
    const cw = (W - 0.6) / 3;
    items.forEach(([n, t, d], i) => {
      const x = X0 + i * (cw + 0.3);
      card(s, x, TOP, cw, 3.15);
      T(s, n, { x: x + 0.4, y: TOP + 0.35, w: 2, h: 0.7, fontFace: "Arial", fontSize: 40, bold: true, color: K.volt });
      T(s, t, { x: x + 0.4, y: TOP + 1.2, w: cw - 0.8, h: 0.45, fontFace: "Arial", fontSize: 20, bold: true, color: K.white });
      T(s, d, { x: x + 0.4, y: TOP + 1.75, w: cw - 0.8, h: 1.2, fontSize: 16, color: K.slate });
    });
    strip(s, I.LuLightbulb, "One problem for the owner, one for the member. We solve both in a single platform.", 5.5);
  }
  s.addNotes("Most gyms today manage members, payments and attendance by hand or across disconnected tools: a register at the desk, a spreadsheet, and a WhatsApp group. That costs owners time and money, because dues and renewals slip through the cracks. On the member side, there’s no proper place to keep a history of their fitness progress, so motivation fades. We want to solve both problems with one platform.");

  // ===== 3 SOLUTION =====
  pres.addSection({ title: "The product" });
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "The product" });
  head(s, "The solution", "One platform for the whole gym");
  T(s, "Instead of juggling registers, spreadsheets and chat groups, owners run everything from one dashboard, and every member gets a portal of their own.",
    { x: X0, y: TOP + 0.1, w: 4.6, h: 3.6, fontFace: "Arial", fontSize: 24, bold: true, color: K.white, lineSpacingMultiple: 1.15 });
  {
    const rows = [
      ["LuHome", "Gym management", "Members, trainers, plans, attendance, payments and reports."],
      ["LuQrCode", "QR registration", "Each gym gets its own QR code; members sign up from their phone."],
      ["LuSmartphone", "Member dashboard", "Membership, attendance, payments, workout and diet plans."],
      ["LuScanLine", "AI body scan", "Monthly photo-based progress reports and comparisons."],
      ["LuGlobe", "Gym website", "A premium branded site with plans, trainers and WhatsApp."],
    ];
    const rx = 5.75, rw = X0 + W - rx;
    for (let i = 0; i < rows.length; i++) {
      const y = TOP + i * 0.95;
      s.addShape(pres.shapes.LINE, { x: rx, y, w: rw, h: 0, line: { color: K.ink700, width: 1.25 } });
      await tile(s, rows[i][0], rx, y + 0.18, 0.6);
      T(s, rows[i][1], { x: rx + 0.85, y: y + 0.14, w: rw - 0.85, h: 0.36, fontFace: "Arial", fontSize: 17, bold: true, color: K.white });
      T(s, rows[i][2], { x: rx + 0.85, y: y + 0.5, w: rw - 0.85, h: 0.36, fontSize: 14, color: K.slate });
    }
  }
  s.addNotes("Our platform combines five things gyms currently buy separately or don’t have at all: gym management, QR-based registration, member dashboards, AI-assisted body progress tracking and a professional gym website. It’s a multi-tenant SaaS, which means every gym uses the same software but its data stays completely separate.");

  // ===== 4 HOW IT WORKS =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "The product" });
  head(s, "How it works", "From QR code to results");
  {
    const steps = [
      ["01", "Gym signs up", "Owner gets an admin account and a private workspace."],
      ["02", "Unique QR code", "Generated for the gym and placed at the front desk."],
      ["03", "Member registers", "Scan, fill the form, verify the mobile number by OTP."],
      ["04", "Owner approves", "Membership activated by approval or online payment."],
      ["05", "Member portal", "Plans, attendance, payments and progress on mobile."],
      ["06", "Monthly AI scan", "Front, side and back photos every 30 days."],
    ];
    const gap = 0.32, sw = (W - 5 * gap) / 6, h = 3.05;
    steps.forEach(([n, t, d], i) => {
      const x = X0 + i * (sw + gap);
      const last = i === steps.length - 1;
      card(s, x, TOP, sw, h, last ? { fill: K.volt, line: K.volt } : {});
      T(s, n, { x: x + 0.22, y: TOP + 0.25, w: sw - 0.44, h: 0.5, fontFace: "Arial", fontSize: 26, bold: true, color: last ? K.ink950 : K.volt });
      T(s, t, { x: x + 0.22, y: TOP + 0.85, w: sw - 0.44, h: 0.65, fontFace: "Arial", fontSize: 15, bold: true, color: last ? K.ink950 : K.white });
      T(s, d, { x: x + 0.22, y: TOP + 1.55, w: sw - 0.44, h: 1.35, fontSize: 14, color: last ? K.ink800 : K.slate });
      if (!last) s.addShape(pres.shapes.LINE, { x: x + sw + 0.05, y: TOP + h / 2, w: gap - 0.1, h: 0, line: { color: K.ink600, width: 2, endArrowType: "triangle" } });
    });
    strip(s, I.LuLock, "Every gym is its own tenant: members, payments and photos are never visible to another gym.", 5.4);
  }
  s.addNotes("Here’s the end-to-end flow. A gym owner signs up and gets an admin account. The system generates a unique QR code for that gym. A new member scans it at the front desk, lands on that gym’s registration page, fills in their details and verifies their mobile number by OTP. The owner approves the membership, or it activates on online payment. From then on the member has their own dashboard, and every 30 days they can upload a new body scan.");

  // ===== 5 AI SCAN =====
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "The product" });
  head(s, "Our differentiator", "AI body transformation tracking");
  {
    const items = [
      ["LuCamera", "Upload front, side and back photos", "Quality-checked on upload, stored privately, processed only with the member’s consent."],
      ["LuSparkles", "AI writes a visual report", "Posture, proportions, visible changes and points to discuss with a trainer."],
      ["LuGitCompare", "Compare any two scans", "Every scan is kept forever, so members see a month-by-month timeline."],
    ];
    for (let i = 0; i < items.length; i++) {
      const y = TOP + i * 1.3;
      await tile(s, items[i][0], X0, y, 0.6);
      T(s, items[i][1], { x: X0 + 0.85, y: y + 0.02, w: 5.3, h: 0.38, fontFace: "Arial", fontSize: 17, bold: true, color: K.white });
      T(s, items[i][2], { x: X0 + 0.85, y: y + 0.42, w: 5.3, h: 0.7, fontSize: 15, color: K.slate });
    }
    const cx = 7.15, cw = X0 + W - cx;
    card(s, cx, TOP, cw, 3.95, { fill: K.ink900 });
    T(s, "One scan every 30 days", { x: cx + 0.4, y: TOP + 0.35, w: cw - 0.8, h: 0.45, fontFace: "Arial", fontSize: 22, bold: true, color: K.white });
    const lx = cx + 0.55, lw = cw - 1.4, ty = TOP + 1.25;
    s.addShape(pres.shapes.LINE, { x: lx + 0.15, y: ty + 0.15, w: lw, h: 0, line: { color: K.ink600, width: 3 } });
    [["1 Jan", "Baseline"], ["31 Jan", "Scan 2"], ["2 Mar", "Scan 3"], ["1 Apr", "Scan 4"]].forEach(([d, l], i) => {
      const x = lx + i * (lw / 3);
      s.addShape(pres.shapes.OVAL, { x, y: ty, w: 0.3, h: 0.3, fill: { color: K.volt }, line: { type: "none" } });
      T(s, d, { x: x - 0.1, y: ty + 0.45, w: 1.3, h: 0.35, fontFace: "Arial", fontSize: 16, bold: true, color: K.white });
      T(s, l, { x: x - 0.1, y: ty + 0.8, w: 1.3, h: 0.3, fontSize: 14, color: K.slate });
    });
    card(s, cx + 0.4, TOP + 2.55, cw - 0.8, 1.05, { fill: K.ink950, line: K.ink600, dash: "dash", r: 0.1 });
    T(s, "“Your next body scan will be available on 2 March. Please complete your current 30-day fitness journey.”",
      { x: cx + 0.65, y: TOP + 2.55, w: cw - 1.3, h: 1.05, fontSize: 15, italic: true, color: K.white, valign: "middle" });
    s.addImage({ data: I.LuAlertTriangle, x: X0, y: 6.22, w: 0.3, h: 0.3, altText: "" });
    T(s, "30-day rule enforced on the server. Visual observations only: no body-fat %, muscle mass or medical claims.",
      { x: X0 + 0.45, y: 6.18, w: W - 0.5, h: 0.4, fontSize: 14, color: K.slate, valign: "middle" });
  }
  s.addNotes("This is our main differentiator. At joining, members upload front, side and back photos. A vision-capable AI analyses what’s visible and writes an informational report: posture, proportions, visible changes, and things to discuss with a trainer. After 30 days they can upload again and compare against any earlier scan. The 30-day rule is enforced on the server, and old scans are never overwritten. We are careful not to make medical claims: ordinary photos can’t reliably measure body fat or muscle mass, so the reports are clearly labelled as visual observations.");

  // ===== 6 ROLES =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "The product" });
  head(s, "Who uses it", "Four roles, one connected system");
  {
    const roles = [
      ["LuKey", "Super admin", "That’s us", true, "Runs the platform: gyms, subscriptions, revenue, AI usage and support."],
      ["LuBuilding2", "Gym owner", "Our customer", false, "Members, trainers, plans, attendance, payments, reports and website."],
      ["LuGraduationCap", "Trainer", "Gym staff", false, "Assigned members, workout and diet plans, progress and scan comparisons."],
      ["LuUsers", "Member", "End user", false, "Profile, membership, attendance, payments, plans and transformation history."],
    ];
    const cw = (W - 0.9) / 4;
    for (let i = 0; i < roles.length; i++) {
      const [ic, t, tag, solid, d] = roles[i];
      const x = X0 + i * (cw + 0.3);
      card(s, x, TOP, cw, 4.25);
      await tile(s, ic, x + 0.35, TOP + 0.35, 0.65);
      T(s, t, { x: x + 0.35, y: TOP + 1.25, w: cw - 0.7, h: 0.45, fontFace: "Arial", fontSize: 21, bold: true, color: K.white });
      pill(s, tag, x + 0.35, TOP + 1.85, solid ? {} : { fill: K.volt, fillT: 86, color: K.volt });
      T(s, d, { x: x + 0.35, y: TOP + 2.45, w: cw - 0.7, h: 1.6, fontSize: 16, color: K.slate });
    }
  }
  s.addNotes("The platform has four roles. We are the super admin: we see every gym, manage subscriptions and track platform revenue and AI usage. The gym owner is our paying customer and runs their business from their own dashboard. Trainers see only the members assigned to them and manage workout and diet plans. Members see only their own data. Each role is restricted on the server, not just in the interface.");

  // ===== 7 PROTOTYPE =====
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "The product" });
  head(s, "Already built", "It works today: a running prototype");
  {
    const mx = X0, mw = 5.7, mh = 4.75;
    card(s, mx, TOP, mw, mh, { glow: true });
    T(s, "My transformation", { x: mx + 0.35, y: TOP + 0.3, w: 3.2, h: 0.4, fontFace: "Arial", fontSize: 17, bold: true, color: K.white, valign: "middle" });
    pill(s, "Sample screen", mx + mw - 1.85, TOP + 0.33, { fill: K.volt, fillT: 86, color: K.volt, w: 1.5 });
    const pw = (mw - 0.7 - 0.25) / 2;
    [["1 Jan · Baseline", K.ink600], ["31 Jan · Scan 2", K.volt]].forEach(([lab, col], i) => {
      const px = mx + 0.35 + i * (pw + 0.25), py = TOP + 0.95;
      card(s, px, py, pw, 1.75, { fill: K.ink800, r: 0.1 });
      s.addShape(pres.shapes.OVAL, { x: px + pw / 2 - 0.19, y: py + 0.2, w: 0.38, h: 0.38, fill: { color: col }, line: { type: "none" } });
      s.addShape(pres.shapes.ROUNDED_RECTANGLE, { x: px + pw / 2 - 0.36, y: py + 0.66, w: 0.72, h: 0.95, rectRadius: 0.3, fill: { color: col }, line: { type: "none" } });
      T(s, lab, { x: px, y: py + 1.82, w: pw, h: 0.3, fontSize: 13, color: K.slate300 });
    });
    card(s, mx + 0.35, TOP + 3.05, mw - 0.7, 0.72, { fill: K.ink950, r: 0.1 });
    T(s, "AI observation: “Posture looks more upright in the side view.”", { x: mx + 0.55, y: TOP + 3.05, w: mw - 1.1, h: 0.72, fontSize: 14, color: K.slate, valign: "middle" });
    card(s, mx + 0.35, TOP + 3.95, mw - 0.7, 0.55, { fill: K.ink800, line: K.ink600, r: 0.1 });
    T(s, "Next scan unlocks on 2 Mar", { x: mx + 0.35, y: TOP + 3.95, w: mw - 0.7, h: 0.55, fontSize: 14, bold: true, color: K.slate300, align: "center", valign: "middle" });

    const rx = 6.85, rw = X0 + W - rx;
    T(s, "WORKING IN THE PROTOTYPE", { x: rx, y: TOP, w: rw, h: 0.35, fontFace: "Arial", fontSize: 13, bold: true, color: K.volt, charSpacing: 3 });
    const built = [
      "QR join per branch, OTP sign-up, baseline photos",
      "Member portal: membership, attendance, progress",
      "AI body scanner with photo-quality checks",
      "30-day rule on the server, full scan history",
      "Side-by-side comparison of any two scans",
      "Razorpay payments and admin membership approvals",
      "Role-based access, scoped to each gym",
      "Public gym website pages",
    ];
    built.forEach((b, i) => {
      const y = TOP + 0.55 + i * 0.52;
      s.addImage({ data: I.LuCheck, x: rx, y: y + 0.05, w: 0.26, h: 0.26, altText: "Done" });
      T(s, b, { x: rx + 0.45, y, w: rw - 0.45, h: 0.42, fontSize: 15, color: K.slate300, valign: "middle" });
    });
  }
  s.addNotes("This isn’t just an idea on paper. We already have a working prototype, codenamed IronForge, built on Laravel, MySQL and Tailwind. The core flows run end to end: QR registration with OTP, the member portal, the AI body scanner with photo-quality checks, the server-side 30-day rule, scan comparisons, Razorpay payments, role-based access and the public gym website. The screen on the left is a simplified mock of the transformation page with sample text. What’s left is mainly the commercial layer, which I’ll cover in the build plan.");

  // ===== 8 GYM VALUE =====
  pres.addSection({ title: "Who benefits" });
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "Who benefits" });
  head(s, "How gyms benefit", "What gyms gain");
  {
    const items = [
      ["LuClipboardList", "Less paperwork", "Registration, attendance and fees go digital."],
      ["LuWallet", "Fewer missed dues", "Payments, dues and renewals tracked in one place."],
      ["LuQrCode", "Faster onboarding", "New members join by scanning a QR at the desk."],
      ["LuRepeat", "Better retention", "Monthly progress gives members a reason to stay."],
      ["LuStar", "Premium brand", "A professional website and branded member portal."],
      ["LuBarChart3", "Clear numbers", "Revenue, membership and attendance reports."],
    ];
    const cw = (W - 0.6) / 3, ch = 2.25;
    for (let i = 0; i < items.length; i++) {
      const x = X0 + (i % 3) * (cw + 0.3), y = TOP + Math.floor(i / 3) * (ch + 0.3);
      card(s, x, y, cw, ch);
      await tile(s, items[i][0], x + 0.35, y + 0.35, 0.6);
      T(s, items[i][1], { x: x + 0.35, y: y + 1.12, w: cw - 0.7, h: 0.4, fontFace: "Arial", fontSize: 19, bold: true, color: K.white });
      T(s, items[i][2], { x: x + 0.35, y: y + 1.55, w: cw - 0.7, h: 0.6, fontSize: 15, color: K.slate });
    }
  }
  s.addNotes("For the gym owner, the value is simple. Less paperwork, because registration, attendance and fees go digital. Fewer missed dues, because every payment and renewal is tracked in one place. Faster onboarding through the QR code. Better retention, because members can see their monthly progress. A more premium brand image with their own website and member portal. And clear reports so they can make decisions based on numbers.");

  // ===== 9 STATEMENT =====
  s = pres.addSlide({ masterName: "STATEMENT", sectionTitle: "Who benefits" });
  s.addText("WHY GYMS WILL PAY FOR IT", { placeholder: "eyebrow" });
  s.addText("Members who can see their progress have a reason to stay.", { placeholder: "title" });
  s.addText("The AI transformation tracker is the retention story we sell to every gym owner.", { placeholder: "body" });
  s.addNotes("This is the heart of the sales pitch to gyms. Every gym owner worries about members dropping off after a month or two. When a member can open their phone and see their own transformation, month after month, they have a concrete reason to keep coming back and to renew. That’s what makes our product more than another billing tool.");

  // ===== 10 OUR VALUE =====
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "Who benefits" });
  head(s, "How we benefit", "What we gain");
  {
    const items = [
      ["LuRepeat", "Recurring revenue", "Predictable monthly and annual subscriptions."],
      ["LuLayers", "Scales cheaply", "One codebase serves every gym we sign."],
      ["LuTrendingUp", "Many upsells", "Websites, setup, branding, branches, AI credits."],
      ["LuDatabase", "Sticky customers", "Members, payments and scan history live with us."],
      ["LuMegaphone", "Growth loop", "Every member sees our product; gyms refer gyms."],
      ["LuMapPin", "Room to expand", "Jaipur first, then other cities across India."],
    ];
    const colW = (W - 0.6) / 2;
    for (let i = 0; i < items.length; i++) {
      const x = X0 + (i % 2) * (colW + 0.6), y = TOP + Math.floor(i / 2) * 1.55;
      s.addShape(pres.shapes.LINE, { x, y, w: colW, h: 0, line: { color: K.ink700, width: 1.25 } });
      await tile(s, items[i][0], x, y + 0.35, 0.7);
      T(s, items[i][1], { x: x + 0.95, y: y + 0.32, w: colW - 0.95, h: 0.42, fontFace: "Arial", fontSize: 20, bold: true, color: K.white });
      T(s, items[i][2], { x: x + 0.95, y: y + 0.78, w: colW - 0.95, h: 0.4, fontSize: 15, color: K.slate });
    }
  }
  s.addNotes("For MDDInfotech, this is a recurring-revenue business. Because it’s multi-tenant, one codebase serves every gym, so adding a new gym is mostly onboarding, not new development. On top of subscriptions there are several upsells. Once a gym’s members, payments and scan history are on our platform, switching away is hard, which keeps churn low. Every member who registers sees our product, and happy gym owners refer other owners. And once Jaipur is proven, the same product can be sold in any city.");

  // ===== 11 PRICING =====
  pres.addSection({ title: "Revenue model" });
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "Revenue model" });
  head(s, "Revenue model", "Simple subscription pricing");
  {
    const plans = [
      ["Starter", "₹999", ["Gym dashboard", "Member management", "QR registration", "Attendance", "Membership management"], false],
      ["Pro", "₹1,999", ["Everything in Starter", "Member dashboard", "AI progress tracking", "Workout and diet plans", "Reports"], true],
      ["Enterprise", "₹4,999", ["Everything in Pro", "Multiple branches", "Advanced analytics", "Custom branding", "Priority support"], false],
    ];
    const cw = (W - 0.6) / 3;
    plans.forEach(([name, price, feats, pro], i) => {
      const x = X0 + i * (cw + 0.3);
      card(s, x, TOP, cw, 4.3, pro ? { line: K.volt, lineW: 2, glow: true } : {});
      T(s, name, { x: x + 0.4, y: TOP + 0.35, w: 2.2, h: 0.45, fontFace: "Arial", fontSize: 22, bold: true, color: K.white, valign: "middle" });
      if (pro) pill(s, "Recommended", x + cw - 1.95, TOP + 0.4, { w: 1.55 });
      T(s, price, { x: x + 0.4, y: TOP + 0.95, w: cw - 0.8, h: 0.75, fontFace: "Arial", fontSize: 40, bold: true, color: pro ? K.volt : K.white });
      T(s, "per gym, per month", { x: x + 0.4, y: TOP + 1.7, w: cw - 0.8, h: 0.3, fontSize: 13, color: K.slate });
      feats.forEach((f, j) => {
        const y = TOP + 2.2 + j * 0.39;
        s.addImage({ data: I.LuCheck, x: x + 0.4, y: y + 0.06, w: 0.22, h: 0.22, altText: "Included" });
        T(s, f, { x: x + 0.75, y, w: cw - 1.15, h: 0.34, fontSize: 15, color: K.slate300, valign: "middle" });
      });
    });
    T(s, "Monthly or annual billing. Initial prices, to be validated with pilot gyms against demand and operating costs.",
      { x: X0, y: 6.45, w: W, h: 0.35, fontSize: 13, color: K.slate });
  }
  s.addNotes("We sell on monthly and annual subscriptions in three tiers. Starter at 999 rupees a month covers the core management features. Pro at 1,999 adds the member dashboard and the AI progress tracking, which is our differentiator, so we expect to steer most gyms here. Enterprise at 4,999 is for multi-branch gyms and adds analytics, custom branding and priority support. These are starting prices; we’ll validate them with our pilot gyms.");

  // ===== 12 ADD-ONS =====
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "Revenue model" });
  head(s, "Revenue model", "Revenue beyond the subscription");
  {
    const items = [
      ["LuGlobe", "Gym websites", "Premium branded sites, built and hosted by us."],
      ["LuRocket", "Setup fees", "One-time onboarding for each new gym."],
      ["LuPalette", "Custom branding", "The gym’s own logo, colours and domain."],
      ["LuBuilding2", "Extra branches", "An added fee for each additional location."],
      ["LuSparkles", "AI scan credits", "Premium AI scans beyond the plan allowance."],
      ["LuCalendarCheck", "Annual plans", "Yearly plans paid upfront for better cash flow."],
    ];
    const cw = (W - 1.0) / 6;
    for (let i = 0; i < items.length; i++) {
      const x = X0 + i * (cw + 0.2);
      card(s, x, TOP, cw, 4.0, { fill: K.ink900 });
      await tile(s, items[i][0], x + (cw - 0.9) / 2, TOP + 0.45, 0.9);
      T(s, items[i][1], { x: x + 0.2, y: TOP + 1.65, w: cw - 0.4, h: 0.75, fontFace: "Arial", fontSize: 17, bold: true, color: K.white, align: "center" });
      T(s, items[i][2], { x: x + 0.2, y: TOP + 2.45, w: cw - 0.4, h: 1.4, fontSize: 14, color: K.slate, align: "center" });
    }
    T(s, "Add-on prices to be set after the pilot.", { x: X0, y: 6.3, w: W, h: 0.35, fontSize: 13, color: K.slate });
  }
  s.addNotes("Beyond the monthly subscription, there are six more ways to earn from each gym: building and hosting their website, a one-time setup and onboarding fee, custom branding, a fee per additional branch, premium AI scan credits, and annual plans paid upfront. Prices for these add-ons are still to be set.");

  // ===== 13 REVENUE =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "Revenue model" });
  head(s, "The growth math", "Revenue grows with every gym");
  s.addChart(pres.charts.BAR, [{ name: "Monthly gross revenue", labels: ["10 gyms", "25 gyms", "50 gyms", "100 gyms", "200 gyms"], values: [19990, 49975, 99950, 199900, 399800] }], {
    x: X0, y: TOP, w: 7.3, h: 4.75, barDir: "col", barGapWidthPct: 60,
    chartColors: [HEX.volt], showLegend: false,
    showTitle: true, title: "Monthly gross subscription revenue (₹)", titleColor: HEX.white, titleFontSize: 14, titleFontFace: "+mn-lt",
    showValue: true, dataLabelPosition: "outEnd", dataLabelColor: HEX.white, dataLabelFontSize: 12, dataLabelFontFace: "+mn-lt",
    dataLabelFormatCode: '[>=100000]"₹"##\\,##\\,##0;"₹"##,##0',
    catAxisLabelColor: HEX.slate, catAxisLabelFontSize: 13, catAxisLabelFontFace: "+mn-lt",
    catAxisLineShow: true, catAxisLineColor: HEX.ink600,
    valAxisHidden: true, valGridLine: { style: "none" }, catGridLine: { style: "none" },
    valAxisMaxVal: 460000,
  });
  {
    const kx = 8.3, kw = X0 + W - kx;
    [["₹2 lakh", "per month at 100 gyms"], ["₹24 lakh", "per year at 100 gyms"], ["₹48 lakh", "per year at 200 gyms"]].forEach(([v, l], i) => {
      const y = TOP + i * 1.3;
      card(s, kx, y, kw, 1.1);
      T(s, v, { x: kx + 0.35, y: y + 0.15, w: kw - 0.7, h: 0.55, fontFace: "Arial", fontSize: 30, bold: true, color: K.volt });
      T(s, l, { x: kx + 0.35, y: y + 0.7, w: kw - 0.7, h: 0.3, fontSize: 14, color: K.slate });
    });
    T(s, "At a ₹1,999 average per gym. Gross, not profit: hosting, AI, storage, marketing, support and taxes come off this.",
      { x: kx, y: TOP + 3.95, w: kw, h: 0.8, fontSize: 12, color: K.slate });
  }
  s.addNotes("Here is the simple growth math, assuming an average of 1,999 rupees per gym per month. Ten gyms is about 20 thousand a month; 100 gyms is about 2 lakh a month, or roughly 24 lakh a year; 200 gyms is about 48 lakh a year. These are gross subscription figures before add-ons, and before costs like hosting, AI usage, storage, marketing, support and taxes.");

  // ===== 14 GTM =====
  pres.addSection({ title: "Sales strategy" });
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "Sales strategy" });
  head(s, "Sales strategy", "Win Jaipur first, then expand");
  {
    const stages = [
      ["STAGE 1", "Reach", ["List every gym in Jaipur", "Call and WhatsApp owners", "Book in-person meetings"]],
      ["STAGE 2", "Convert", ["Demo the live software", "Offer a 14-day free trial", "Convert to a paid plan"]],
      ["STAGE 3", "Grow", ["Collect feedback, improve", "Referrals from happy gyms", "Expand to new cities"]],
    ];
    const cw = (W - 0.6) / 3;
    stages.forEach(([e, t, steps], i) => {
      const x = X0 + i * (cw + 0.3), hot = i === 1;
      card(s, x, TOP, cw, 3.25, hot ? { fill: K.volt, line: K.volt } : { fill: K.ink900 });
      T(s, e, { x: x + 0.4, y: TOP + 0.35, w: 2, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, charSpacing: 3, color: hot ? K.ink950 : K.volt });
      T(s, t, { x: x + 0.4, y: TOP + 0.7, w: cw - 0.8, h: 0.55, fontFace: "Arial", fontSize: 26, bold: true, color: hot ? K.ink950 : K.white });
      steps.forEach((st, j) => {
        const y = TOP + 1.5 + j * 0.52;
        T(s, String(i * 3 + j + 1), { x: x + 0.4, y, w: 0.35, h: 0.4, fontFace: "Arial", fontSize: 16, bold: true, color: hot ? K.ink950 : K.volt, valign: "middle" });
        T(s, st, { x: x + 0.8, y, w: cw - 1.1, h: 0.4, fontSize: 15, color: hot ? K.ink800 : K.slate300, valign: "middle" });
      });
    });
    strip(s, I.LuPlay, "The prototype means we can demo real software in the very first meeting.", 5.5);
  }
  s.addNotes("We start local and direct. First we build a list of gyms in Jaipur and reach owners by phone and WhatsApp to book in-person meetings. In the meeting we demo the actual software, which we can do because the prototype already runs, and offer a 14-day free trial. Then we convert trial gyms to paid plans, collect feedback, ask happy owners for referrals, and once the product is validated locally we expand to other cities.");

  // ===== 15 CHANNELS =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "Sales strategy" });
  head(s, "Sales strategy", "Who we target and how we reach them");
  {
    const lw = 5.2;
    card(s, X0, TOP, lw, 4.5);
    T(s, "TARGET CUSTOMERS", { x: X0 + 0.4, y: TOP + 0.35, w: 4, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, color: K.volt, charSpacing: 3 });
    const targets = [["LuDumbbell", "Independent gyms"], ["LuStar", "Premium fitness clubs"], ["LuUserCheck", "Personal training studios"], ["LuBuilding2", "Multi-branch gym chains"], ["LuActivity", "Fitness centers"]];
    for (let i = 0; i < targets.length; i++) {
      const y = TOP + 0.85 + i * 0.68;
      await tile(s, targets[i][0], X0 + 0.4, y, 0.5);
      T(s, targets[i][1], { x: X0 + 1.1, y, w: lw - 1.4, h: 0.5, fontSize: 17, color: K.white, valign: "middle" });
    }
    const rx = X0 + lw + 0.3, rw = X0 + W - rx;
    card(s, rx, TOP, rw, 4.5);
    T(s, "DIGITAL CHANNELS", { x: rx + 0.4, y: TOP + 0.35, w: 4, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, color: K.volt, charSpacing: 3 });
    const chans = ["Instagram Reels", "Facebook ads", "Google Ads", "YouTube demos", "WhatsApp Business", "LinkedIn outreach", "Trainer partnerships"];
    let px = rx + 0.4, py = TOP + 0.9;
    for (const c of chans) {
      const w = c.length * 0.115 + 0.6;
      if (px + w > rx + rw - 0.4) { px = rx + 0.4; py += 0.75; }
      pill(s, c, px, py, { w, h: 0.55, fontSize: 16, fill: K.ink800, line: K.ink600, color: K.white });
      px += w + 0.2;
    }
  }
  s.addNotes("Our first customers are independent gyms, premium fitness clubs, personal training studios, multi-branch chains and fitness centers. Alongside direct sales, we’ll support the pipeline with Instagram Reels showing real transformations, Facebook and Google ads, YouTube software demos, WhatsApp Business, LinkedIn outreach to gym owners, and partnerships with fitness trainers who can recommend us.");

  // ===== 16 ROADMAP =====
  pres.addSection({ title: "Plan and technology" });
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "Plan and technology" });
  head(s, "Build plan", "Build plan in three stages");
  {
    const stages = [
      ["PHASES 1–5", "MVP", "Core working in prototype", true, ["Multi-tenant foundation and roles", "Gym admin: members, trainers, plans", "QR registration with OTP", "Member dashboard", "Attendance and payments"]],
      ["PHASES 6–7", "AI fitness", "Working in prototype", true, ["Private photo upload and quality checks", "AI visual analysis", "30-day rule on the server", "Scan history and comparisons"]],
      ["PHASES 8–10", "Commercial SaaS", "Next to build", false, ["Gym subscription billing", "Plan-based feature limits", "Super admin revenue and AI usage", "Gym website builder and analytics", "MCP layer for AI agents", "Security testing and launch"]],
    ];
    const cw = (W - 0.6) / 3;
    stages.forEach(([ph, t, st, done, list], i) => {
      const x = X0 + i * (cw + 0.3);
      card(s, x, TOP, cw, 3.6, done ? { fill: K.ink900 } : { fill: K.ink900, line: K.volt, lineW: 2 });
      T(s, ph, { x: x + 0.35, y: TOP + 0.3, w: 3, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, charSpacing: 3, color: K.volt });
      T(s, t, { x: x + 0.35, y: TOP + 0.62, w: cw - 0.7, h: 0.45, fontFace: "Arial", fontSize: 22, bold: true, color: K.white });
      pill(s, st, x + 0.35, TOP + 1.15, done ? { fill: K.volt, fillT: 86, color: K.volt } : { fill: K.ink800, line: K.ink600, color: K.slate300 });
      T(s, list.map((l, j) => ({ text: l, options: { bullet: true, breakLine: j < list.length - 1 } })),
        { x: x + 0.35, y: TOP + 1.7, w: cw - 0.6, h: 1.8, fontSize: 14, color: K.slate300, paraSpaceAfter: 3 });
    });
    card(s, X0, 5.8, W, 0.85, { fill: K.volt, transparency: 92, line: K.volt, lineT: 55 });
    s.addImage({ data: I.LuUsers, x: X0 + 0.35, y: 6.04, w: 0.38, h: 0.38, altText: "" });
    T(s, [{ text: "Team: ", options: { bold: true, color: K.volt } }, { text: "Ekansh (lead) and Naresh", options: { color: K.white } }],
      { x: X0 + 0.95, y: 5.8, w: 4.4, h: 0.85, fontSize: 17, valign: "middle" });
    s.addImage({ data: I.LuClock, x: X0 + 5.5, y: 6.04, w: 0.38, h: 0.38, altText: "" });
    T(s, [{ text: "Timeline: ", options: { bold: true, color: K.volt } }, { text: "8 days, finished by next Saturday", options: { color: K.white } }],
      { x: X0 + 6.1, y: 5.8, w: W - 6.4, h: 0.85, fontSize: 17, valign: "middle" });
  }
  s.addNotes("We’re building in three stages and ten phases. The prototype already covers the core of the first two stages: the multi-tenant foundation, gym admin, QR registration, the member dashboard, and the full AI body scan flow. What’s left is the commercial layer: billing gyms for their subscriptions, limiting features by plan, the super admin revenue view, the website builder, analytics, an MCP integration layer, and security testing. Ekansh leads the project with Naresh as the second developer, and our target is to finish the build in 8 days, by next Saturday, then pilot with three to five gyms in Jaipur.");

  // ===== 17 STACK =====
  s = pres.addSlide({ masterName: "CONTENT", sectionTitle: "Plan and technology" });
  head(s, "Technology and trust", "A proven, secure technology stack");
  {
    const rows = [["Backend", "Laravel (PHP)"], ["Frontend", "Blade + Tailwind CSS"], ["Database", "MySQL"], ["AI analysis", "Vision-capable AI API"], ["Photo storage", "Private, S3-compatible"], ["Payments", "Razorpay"], ["Notifications", "SMS OTP, email, WhatsApp"], ["Hosting", "Cloud VPS"]];
    const hdr = { bold: true, color: K.volt, fill: { color: K.ink800 } };
    const data = [[{ text: "Layer", options: hdr }, { text: "Technology", options: hdr }]].concat(
      rows.map(([a, b]) => [{ text: a, options: { color: K.slate } }, { text: b, options: { color: K.white } }]));
    s.addTable(data, { x: X0, y: TOP, w: 6.3, colW: [2.3, 4.0], rowH: 0.5, fontSize: 15, fontFace: "Calibri",
      border: { type: "solid", color: HEX.ink700, pt: 1 }, fill: { color: K.ink850 }, valign: "middle", margin: [0, 0.15, 0, 0.2] });
    const rx = X0 + 6.7, rw = X0 + W - rx;
    card(s, rx, TOP, rw, 4.5);
    T(s, "TRUST BUILT IN", { x: rx + 0.4, y: TOP + 0.35, w: 4, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, color: K.volt, charSpacing: 3 });
    const trust = ["Each gym’s data fully isolated", "Body photos private, with member consent", "30-day rule enforced on the server", "Verified payment webhooks", "Scheduled database and photo backups", "Audit log of important admin actions", "Future MCP layer for AI agents"];
    trust.forEach((t, i) => {
      const y = TOP + 0.8 + i * 0.5;
      s.addImage({ data: I.LuShieldCheck, x: rx + 0.4, y: y + 0.06, w: 0.26, h: 0.26, altText: "" });
      T(s, t, { x: rx + 0.85, y, w: rw - 1.2, h: 0.4, fontSize: 15, color: K.slate300, valign: "middle" });
    });
  }
  s.addNotes("The stack is mainstream and affordable: Laravel, MySQL, Tailwind, Razorpay for payments, a vision-capable AI API for the scans, private S3-compatible storage for photos, and a cloud VPS. Because we handle body photos, trust is a selling point: each gym’s data is isolated, photos are private and processed only with consent, payment webhooks are verified, backups are scheduled and admin actions are logged. Later, an MCP integration layer will let authorized AI agents work with the platform, always within each user’s permissions.");

  // ===== 18 TEAM =====
  s = pres.addSlide({ masterName: "CONTENT_DEEP", sectionTitle: "Plan and technology" });
  head(s, "The team", "Who is building it");
  {
    const cw = (W - 0.6) / 3, ch = 4.5;
    const people = [
      ["EK", "Ekansh", ["Project lead", "Lead developer", "Designer"], "Leads the build and owns the product design and the IronForge look."],
      ["NA", "Naresh", ["Developer"], "Builds features alongside Ekansh across the platform."],
    ];
    people.forEach(([ini, name, roles, d], i) => {
      const x = X0 + i * (cw + 0.3);
      card(s, x, TOP, cw, ch, { fill: K.ink900 });
      s.addShape(pres.shapes.OVAL, { x: x + 0.4, y: TOP + 0.4, w: 1.0, h: 1.0, fill: { color: i === 0 ? K.volt : K.ink800 }, line: i === 0 ? { type: "none" } : { color: K.volt, width: 1.5 } });
      T(s, ini, { x: x + 0.4, y: TOP + 0.4, w: 1.0, h: 1.0, fontFace: "Arial", fontSize: 26, bold: true, color: i === 0 ? K.ink950 : K.volt, align: "center", valign: "middle" });
      T(s, name, { x: x + 0.4, y: TOP + 1.65, w: cw - 0.8, h: 0.5, fontFace: "Arial", fontSize: 26, bold: true, color: K.white });
      let px = x + 0.4, py = TOP + 2.3;
      roles.forEach((r, j) => {
        const w = r.length * 13 * 0.0078 + 0.45;
        if (px + w > x + cw - 0.4) { px = x + 0.4; py += 0.45; }
        pill(s, r, px, py, j === 0 ? { w, fontSize: 13 } : { w, fontSize: 13, fill: K.volt, fillT: 86, color: K.volt });
        px += w + 0.15;
      });
      T(s, d, { x: x + 0.4, y: py + 0.6, w: cw - 0.8, h: 1.0, fontSize: 16, color: K.slate });
    });
    const x = X0 + 2 * (cw + 0.3);
    card(s, x, TOP, cw, ch, { fill: K.volt, line: K.volt });
    T(s, "TIMELINE", { x: x + 0.4, y: TOP + 0.45, w: cw - 0.8, h: 0.3, fontFace: "Arial", fontSize: 12, bold: true, charSpacing: 3, color: K.ink950 });
    T(s, "8 days", { x: x + 0.4, y: TOP + 0.85, w: cw - 0.8, h: 1.0, fontFace: "Arial", fontSize: 60, bold: true, color: K.ink950 });
    T(s, "Build finished by next Saturday.", { x: x + 0.4, y: TOP + 2.0, w: cw - 0.8, h: 0.8, fontFace: "Arial", fontSize: 20, bold: true, color: K.ink950 });
    T(s, "Then we start signing 3–5 pilot gyms in Jaipur.", { x: x + 0.4, y: TOP + 2.9, w: cw - 0.8, h: 0.8, fontSize: 16, color: K.ink800 });
  }
  s.addNotes("The project is run by a small, focused team. Ekansh leads the project as lead developer and also handles the design, including the IronForge look you’ve seen in this deck. Naresh is the second developer, building features alongside him. Our timeline is 8 days: the build is finished by next Saturday, and then we start signing three to five pilot gyms in Jaipur.");

  // ===== 19 CLOSE =====
  s = pres.addSlide({ masterName: "CLOSING", sectionTitle: "Plan and technology" });
  brand(s, X0, 1.2);
  T(s, [
    { text: "A complete digital fitness ecosystem, for ", options: {} },
    { text: "gyms across India.", options: { color: K.volt } },
  ], { x: X0, y: 2.3, w: 10.5, h: 2.2, fontFace: "Arial", fontSize: 48, bold: true, color: K.white });
  T(s, "MANAGE YOUR GYM  ·  TRACK YOUR MEMBERS  ·  VISUALIZE THEIR TRANSFORMATION",
    { x: X0, y: 4.75, w: 11.5, h: 0.4, fontFace: "Arial", fontSize: 15, bold: true, color: K.volt, charSpacing: 3 });
  presenter(s, X0, 5.9);
  s.addNotes("Our long-term goal is a scalable SaaS platform serving hundreds, and eventually thousands, of gyms across India and potentially beyond. We’re building more than gym management software: a complete digital fitness ecosystem where owners run their business, trainers manage their members, and members can see their transformation. Thank you, we’re happy to take questions.");

  await pres.writeFile({ fileName: OUT });
  await applyTheme(OUT, THEME);
  console.log("wrote", OUT);
})().catch(e => { console.error(e); process.exit(1); });
