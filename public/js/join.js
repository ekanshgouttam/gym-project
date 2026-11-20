"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["join"],{

/***/ "./resources/js/join.js":
/*!******************************!*\
  !*** ./resources/js/join.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ mountJoin)
/* harmony export */ });
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? (c > 1 && (G.n = c < 3 ? -1 : u), d(c, u)) : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
// QR registration: mobile OTP step, then unlock the rest of the form.
function mountJoin(root) {
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var phone = root.querySelector('#phone');
  var sendBtn = root.querySelector('[data-otp-send]');
  var codeWrap = root.querySelector('[data-otp-code]');
  var code = root.querySelector('#otp');
  var verifyBtn = root.querySelector('[data-otp-verify]');
  var msg = root.querySelector('[data-otp-msg]');
  var details = root.querySelector('[data-join-details]');
  var verifiedBadge = root.querySelector('[data-otp-verified]');
  var cooldownTimer = null;
  var say = function say(text) {
    var isError = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    msg.textContent = text;
    msg.className = 'mt-2 text-sm ' + (isError ? 'text-rose-400' : 'text-volt-300');
  };
  function post(_x, _x2) {
    return _post.apply(this, arguments);
  }
  function _post() {
    _post = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(url, body) {
      var res, data, first;
      return _regenerator().w(function (_context3) {
        while (1) switch (_context3.n) {
          case 0:
            _context3.n = 1;
            return fetch(url, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf
              },
              body: JSON.stringify(body)
            });
          case 1:
            res = _context3.v;
            _context3.n = 2;
            return res.json()["catch"](function () {
              return {};
            });
          case 2:
            data = _context3.v;
            if (res.ok) {
              _context3.n = 3;
              break;
            }
            first = data.errors ? Object.values(data.errors)[0][0] : data.message;
            throw new Error(res.status === 429 ? 'Too many attempts. Please wait a few minutes.' : first || 'Something went wrong.');
          case 3:
            return _context3.a(2, data);
        }
      }, _callee3);
    }));
    return _post.apply(this, arguments);
  }
  function cooldown(seconds) {
    clearInterval(cooldownTimer);
    var left = seconds;
    sendBtn.disabled = true;
    sendBtn.textContent = "Resend in ".concat(left, "s");
    cooldownTimer = setInterval(function () {
      left -= 1;
      if (left <= 0) {
        clearInterval(cooldownTimer);
        sendBtn.disabled = false;
        sendBtn.textContent = 'Resend code';
      } else {
        sendBtn.textContent = "Resend in ".concat(left, "s");
      }
    }, 1000);
  }
  function unlock(verifiedPhone) {
    phone.readOnly = true;
    sendBtn.classList.add('hidden');
    codeWrap.classList.add('hidden');
    verifiedBadge.classList.remove('hidden');
    details.classList.remove('hidden');
    details.querySelectorAll('input, select, textarea, button').forEach(function (el) {
      el.disabled = false;
    });
    if (verifiedPhone) phone.value = verifiedPhone.replace(/^\+91/, ''); // "+91" is shown as a prefix
    msg.textContent = '';
  }
  sendBtn === null || sendBtn === void 0 || sendBtn.addEventListener('click', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
    var data, _t;
    return _regenerator().w(function (_context) {
      while (1) switch (_context.p = _context.n) {
        case 0:
          sendBtn.disabled = true;
          _context.p = 1;
          _context.n = 2;
          return post(root.dataset.sendUrl, {
            phone: phone.value
          });
        case 2:
          data = _context.v;
          codeWrap.classList.remove('hidden');
          say(data.message);
          code.focus();
          cooldown(data.resend_after || 30);
          _context.n = 4;
          break;
        case 3:
          _context.p = 3;
          _t = _context.v;
          say(_t.message, true);
          sendBtn.disabled = false;
        case 4:
          return _context.a(2);
      }
    }, _callee, null, [[1, 3]]);
  })));
  verifyBtn === null || verifyBtn === void 0 || verifyBtn.addEventListener('click', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
    var data, _t2;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.p = _context2.n) {
        case 0:
          verifyBtn.disabled = true;
          _context2.p = 1;
          _context2.n = 2;
          return post(root.dataset.verifyUrl, {
            phone: phone.value,
            code: code.value
          });
        case 2:
          data = _context2.v;
          unlock(data.phone);
          _context2.n = 4;
          break;
        case 3:
          _context2.p = 3;
          _t2 = _context2.v;
          say(_t2.message, true);
        case 4:
          _context2.p = 4;
          verifyBtn.disabled = false;
          return _context2.f(4);
        case 5:
          return _context2.a(2);
      }
    }, _callee2, null, [[1, 3, 4, 5]]);
  })));
  code === null || code === void 0 || code.addEventListener('input', function () {
    code.value = code.value.replace(/\D/g, '').slice(0, 6);
    if (code.value.length === 6) verifyBtn.click();
  });
  if (root.dataset.verified === '1') unlock();

  // Plan picker: highlight the selected card.
  root.querySelectorAll('input[name="membership_plan_id"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      root.querySelectorAll('[data-plan-card]').forEach(function (c) {
        return c.classList.remove('ring-2', 'ring-volt-400');
      });
      radio.closest('[data-plan-card]').classList.add('ring-2', 'ring-volt-400');
    });
    if (radio.checked) radio.closest('[data-plan-card]').classList.add('ring-2', 'ring-volt-400');
  });
}

/***/ })

}]);