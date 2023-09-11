(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
		typeof define === 'function' && define.amd ? define(['exports'], factory) :
			(global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global['@meilisearch/autocomplete-client'] = global['@meilisearch/autocomplete-client'] || {}));
}(this, (function (exports) { 'use strict';

	var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

	function createCommonjsModule(fn) {
		var module = { exports: {} };
		return fn(module, module.exports), module.exports;
	}

	var browserPolyfill = createCommonjsModule(function (module) {
		(function (self) {
			(function (exports) {
				var support = {
					searchParams: 'URLSearchParams' in self,
					iterable: 'Symbol' in self && 'iterator' in Symbol,
					blob: 'FileReader' in self && 'Blob' in self && function () {
						try {
							new Blob();
							return true;
						} catch (e) {
							return false;
						}
					}(),
					formData: 'FormData' in self,
					arrayBuffer: 'ArrayBuffer' in self
				};
				function isDataView(obj) {
					return obj && DataView.prototype.isPrototypeOf(obj);
				}
				if (support.arrayBuffer) {
					var viewClasses = ['[object Int8Array]', '[object Uint8Array]', '[object Uint8ClampedArray]', '[object Int16Array]', '[object Uint16Array]', '[object Int32Array]', '[object Uint32Array]', '[object Float32Array]', '[object Float64Array]'];
					var isArrayBufferView = ArrayBuffer.isView || function (obj) {
						return obj && viewClasses.indexOf(Object.prototype.toString.call(obj)) > -1;
					};
				}
				function normalizeName(name) {
					if (typeof name !== 'string') {
						name = String(name);
					}
					if (/[^a-z0-9\-#$%&'*+.^_`|~]/i.test(name)) {
						throw new TypeError('Invalid character in header field name');
					}
					return name.toLowerCase();
				}
				function normalizeValue(value) {
					if (typeof value !== 'string') {
						value = String(value);
					}
					return value;
				}

				// Build a destructive iterator for the value list
				function iteratorFor(items) {
					var iterator = {
						next: function () {
							var value = items.shift();
							return {
								done: value === undefined,
								value: value
							};
						}
					};
					if (support.iterable) {
						iterator[Symbol.iterator] = function () {
							return iterator;
						};
					}
					return iterator;
				}
				function Headers(headers) {
					this.map = {};
					if (headers instanceof Headers) {
						headers.forEach(function (value, name) {
							this.append(name, value);
						}, this);
					} else if (Array.isArray(headers)) {
						headers.forEach(function (header) {
							this.append(header[0], header[1]);
						}, this);
					} else if (headers) {
						Object.getOwnPropertyNames(headers).forEach(function (name) {
							this.append(name, headers[name]);
						}, this);
					}
				}
				Headers.prototype.append = function (name, value) {
					name = normalizeName(name);
					value = normalizeValue(value);
					var oldValue = this.map[name];
					this.map[name] = oldValue ? oldValue + ', ' + value : value;
				};
				Headers.prototype['delete'] = function (name) {
					delete this.map[normalizeName(name)];
				};
				Headers.prototype.get = function (name) {
					name = normalizeName(name);
					return this.has(name) ? this.map[name] : null;
				};
				Headers.prototype.has = function (name) {
					return this.map.hasOwnProperty(normalizeName(name));
				};
				Headers.prototype.set = function (name, value) {
					this.map[normalizeName(name)] = normalizeValue(value);
				};
				Headers.prototype.forEach = function (callback, thisArg) {
					for (var name in this.map) {
						if (this.map.hasOwnProperty(name)) {
							callback.call(thisArg, this.map[name], name, this);
						}
					}
				};
				Headers.prototype.keys = function () {
					var items = [];
					this.forEach(function (value, name) {
						items.push(name);
					});
					return iteratorFor(items);
				};
				Headers.prototype.values = function () {
					var items = [];
					this.forEach(function (value) {
						items.push(value);
					});
					return iteratorFor(items);
				};
				Headers.prototype.entries = function () {
					var items = [];
					this.forEach(function (value, name) {
						items.push([name, value]);
					});
					return iteratorFor(items);
				};
				if (support.iterable) {
					Headers.prototype[Symbol.iterator] = Headers.prototype.entries;
				}
				function consumed(body) {
					if (body.bodyUsed) {
						return Promise.reject(new TypeError('Already read'));
					}
					body.bodyUsed = true;
				}
				function fileReaderReady(reader) {
					return new Promise(function (resolve, reject) {
						reader.onload = function () {
							resolve(reader.result);
						};
						reader.onerror = function () {
							reject(reader.error);
						};
					});
				}
				function readBlobAsArrayBuffer(blob) {
					var reader = new FileReader();
					var promise = fileReaderReady(reader);
					reader.readAsArrayBuffer(blob);
					return promise;
				}
				function readBlobAsText(blob) {
					var reader = new FileReader();
					var promise = fileReaderReady(reader);
					reader.readAsText(blob);
					return promise;
				}
				function readArrayBufferAsText(buf) {
					var view = new Uint8Array(buf);
					var chars = new Array(view.length);
					for (var i = 0; i < view.length; i++) {
						chars[i] = String.fromCharCode(view[i]);
					}
					return chars.join('');
				}
				function bufferClone(buf) {
					if (buf.slice) {
						return buf.slice(0);
					} else {
						var view = new Uint8Array(buf.byteLength);
						view.set(new Uint8Array(buf));
						return view.buffer;
					}
				}
				function Body() {
					this.bodyUsed = false;
					this._initBody = function (body) {
						this._bodyInit = body;
						if (!body) {
							this._bodyText = '';
						} else if (typeof body === 'string') {
							this._bodyText = body;
						} else if (support.blob && Blob.prototype.isPrototypeOf(body)) {
							this._bodyBlob = body;
						} else if (support.formData && FormData.prototype.isPrototypeOf(body)) {
							this._bodyFormData = body;
						} else if (support.searchParams && URLSearchParams.prototype.isPrototypeOf(body)) {
							this._bodyText = body.toString();
						} else if (support.arrayBuffer && support.blob && isDataView(body)) {
							this._bodyArrayBuffer = bufferClone(body.buffer);
							// IE 10-11 can't handle a DataView body.
							this._bodyInit = new Blob([this._bodyArrayBuffer]);
						} else if (support.arrayBuffer && (ArrayBuffer.prototype.isPrototypeOf(body) || isArrayBufferView(body))) {
							this._bodyArrayBuffer = bufferClone(body);
						} else {
							this._bodyText = body = Object.prototype.toString.call(body);
						}
						if (!this.headers.get('content-type')) {
							if (typeof body === 'string') {
								this.headers.set('content-type', 'text/plain;charset=UTF-8');
							} else if (this._bodyBlob && this._bodyBlob.type) {
								this.headers.set('content-type', this._bodyBlob.type);
							} else if (support.searchParams && URLSearchParams.prototype.isPrototypeOf(body)) {
								this.headers.set('content-type', 'application/x-www-form-urlencoded;charset=UTF-8');
							}
						}
					};
					if (support.blob) {
						this.blob = function () {
							var rejected = consumed(this);
							if (rejected) {
								return rejected;
							}
							if (this._bodyBlob) {
								return Promise.resolve(this._bodyBlob);
							} else if (this._bodyArrayBuffer) {
								return Promise.resolve(new Blob([this._bodyArrayBuffer]));
							} else if (this._bodyFormData) {
								throw new Error('could not read FormData body as blob');
							} else {
								return Promise.resolve(new Blob([this._bodyText]));
							}
						};
						this.arrayBuffer = function () {
							if (this._bodyArrayBuffer) {
								return consumed(this) || Promise.resolve(this._bodyArrayBuffer);
							} else {
								return this.blob().then(readBlobAsArrayBuffer);
							}
						};
					}
					this.text = function () {
						var rejected = consumed(this);
						if (rejected) {
							return rejected;
						}
						if (this._bodyBlob) {
							return readBlobAsText(this._bodyBlob);
						} else if (this._bodyArrayBuffer) {
							return Promise.resolve(readArrayBufferAsText(this._bodyArrayBuffer));
						} else if (this._bodyFormData) {
							throw new Error('could not read FormData body as text');
						} else {
							return Promise.resolve(this._bodyText);
						}
					};
					if (support.formData) {
						this.formData = function () {
							return this.text().then(decode);
						};
					}
					this.json = function () {
						return this.text().then(JSON.parse);
					};
					return this;
				}

				// HTTP methods whose capitalization should be normalized
				var methods = ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'POST', 'PUT'];
				function normalizeMethod(method) {
					var upcased = method.toUpperCase();
					return methods.indexOf(upcased) > -1 ? upcased : method;
				}
				function Request(input, options) {
					options = options || {};
					var body = options.body;
					if (input instanceof Request) {
						if (input.bodyUsed) {
							throw new TypeError('Already read');
						}
						this.url = input.url;
						this.credentials = input.credentials;
						if (!options.headers) {
							this.headers = new Headers(input.headers);
						}
						this.method = input.method;
						this.mode = input.mode;
						this.signal = input.signal;
						if (!body && input._bodyInit != null) {
							body = input._bodyInit;
							input.bodyUsed = true;
						}
					} else {
						this.url = String(input);
					}
					this.credentials = options.credentials || this.credentials || 'same-origin';
					if (options.headers || !this.headers) {
						this.headers = new Headers(options.headers);
					}
					this.method = normalizeMethod(options.method || this.method || 'GET');
					this.mode = options.mode || this.mode || null;
					this.signal = options.signal || this.signal;
					this.referrer = null;
					if ((this.method === 'GET' || this.method === 'HEAD') && body) {
						throw new TypeError('Body not allowed for GET or HEAD requests');
					}
					this._initBody(body);
				}
				Request.prototype.clone = function () {
					return new Request(this, {
						body: this._bodyInit
					});
				};
				function decode(body) {
					var form = new FormData();
					body.trim().split('&').forEach(function (bytes) {
						if (bytes) {
							var split = bytes.split('=');
							var name = split.shift().replace(/\+/g, ' ');
							var value = split.join('=').replace(/\+/g, ' ');
							form.append(decodeURIComponent(name), decodeURIComponent(value));
						}
					});
					return form;
				}
				function parseHeaders(rawHeaders) {
					var headers = new Headers();
					// Replace instances of \r\n and \n followed by at least one space or horizontal tab with a space
					// https://tools.ietf.org/html/rfc7230#section-3.2
					var preProcessedHeaders = rawHeaders.replace(/\r?\n[\t ]+/g, ' ');
					preProcessedHeaders.split(/\r?\n/).forEach(function (line) {
						var parts = line.split(':');
						var key = parts.shift().trim();
						if (key) {
							var value = parts.join(':').trim();
							headers.append(key, value);
						}
					});
					return headers;
				}
				Body.call(Request.prototype);
				function Response(bodyInit, options) {
					if (!options) {
						options = {};
					}
					this.type = 'default';
					this.status = options.status === undefined ? 200 : options.status;
					this.ok = this.status >= 200 && this.status < 300;
					this.statusText = 'statusText' in options ? options.statusText : 'OK';
					this.headers = new Headers(options.headers);
					this.url = options.url || '';
					this._initBody(bodyInit);
				}
				Body.call(Response.prototype);
				Response.prototype.clone = function () {
					return new Response(this._bodyInit, {
						status: this.status,
						statusText: this.statusText,
						headers: new Headers(this.headers),
						url: this.url
					});
				};
				Response.error = function () {
					var response = new Response(null, {
						status: 0,
						statusText: ''
					});
					response.type = 'error';
					return response;
				};
				var redirectStatuses = [301, 302, 303, 307, 308];
				Response.redirect = function (url, status) {
					if (redirectStatuses.indexOf(status) === -1) {
						throw new RangeError('Invalid status code');
					}
					return new Response(null, {
						status: status,
						headers: {
							location: url
						}
					});
				};
				exports.DOMException = self.DOMException;
				try {
					new exports.DOMException();
				} catch (err) {
					exports.DOMException = function (message, name) {
						this.message = message;
						this.name = name;
						var error = Error(message);
						this.stack = error.stack;
					};
					exports.DOMException.prototype = Object.create(Error.prototype);
					exports.DOMException.prototype.constructor = exports.DOMException;
				}
				function fetch(input, init) {
					return new Promise(function (resolve, reject) {
						var request = new Request(input, init);
						if (request.signal && request.signal.aborted) {
							return reject(new exports.DOMException('Aborted', 'AbortError'));
						}
						var xhr = new XMLHttpRequest();
						function abortXhr() {
							xhr.abort();
						}
						xhr.onload = function () {
							var options = {
								status: xhr.status,
								statusText: xhr.statusText,
								headers: parseHeaders(xhr.getAllResponseHeaders() || '')
							};
							options.url = 'responseURL' in xhr ? xhr.responseURL : options.headers.get('X-Request-URL');
							var body = 'response' in xhr ? xhr.response : xhr.responseText;
							resolve(new Response(body, options));
						};
						xhr.onerror = function () {
							reject(new TypeError('Network request failed'));
						};
						xhr.ontimeout = function () {
							reject(new TypeError('Network request failed'));
						};
						xhr.onabort = function () {
							reject(new exports.DOMException('Aborted', 'AbortError'));
						};
						xhr.open(request.method, request.url, true);
						if (request.credentials === 'include') {
							xhr.withCredentials = true;
						} else if (request.credentials === 'omit') {
							xhr.withCredentials = false;
						}
						if ('responseType' in xhr && support.blob) {
							xhr.responseType = 'blob';
						}
						request.headers.forEach(function (value, name) {
							xhr.setRequestHeader(name, value);
						});
						if (request.signal) {
							request.signal.addEventListener('abort', abortXhr);
							xhr.onreadystatechange = function () {
								// DONE (success or failure)
								if (xhr.readyState === 4) {
									request.signal.removeEventListener('abort', abortXhr);
								}
							};
						}
						xhr.send(typeof request._bodyInit === 'undefined' ? null : request._bodyInit);
					});
				}
				fetch.polyfill = true;
				if (!self.fetch) {
					self.fetch = fetch;
					self.Headers = Headers;
					self.Request = Request;
					self.Response = Response;
				}
				exports.Headers = Headers;
				exports.Request = Request;
				exports.Response = Response;
				exports.fetch = fetch;
				Object.defineProperty(exports, '__esModule', {
					value: true
				});
				return exports;
			})({});
		})(typeof self !== 'undefined' ? self : commonjsGlobal);
	});

	var instantMeilisearch_umd = createCommonjsModule(function (module, exports) {
		(function (global, factory) {
			factory(exports, browserPolyfill) ;
		})(commonjsGlobal, function (exports) {

			/******************************************************************************
			 Copyright (c) Microsoft Corporation.
			 Permission to use, copy, modify, and/or distribute this software for any
			 purpose with or without fee is hereby granted.
			 THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
			 REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
			 AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
			 INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
			 LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
			 OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
			 PERFORMANCE OF THIS SOFTWARE.
			 ***************************************************************************** */
			var __assign = function () {
				__assign = Object.assign || function __assign(t) {
					for (var s, i = 1, n = arguments.length; i < n; i++) {
						s = arguments[i];
						for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
					}
					return t;
				};
				return __assign.apply(this, arguments);
			};
			function __rest(s, e) {
				var t = {};
				for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0) t[p] = s[p];
				if (s != null && typeof Object.getOwnPropertySymbols === "function") for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
					if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i])) t[p[i]] = s[p[i]];
				}
				return t;
			}
			function __awaiter(thisArg, _arguments, P, generator) {
				function adopt(value) {
					return value instanceof P ? value : new P(function (resolve) {
						resolve(value);
					});
				}
				return new (P || (P = Promise))(function (resolve, reject) {
					function fulfilled(value) {
						try {
							step(generator.next(value));
						} catch (e) {
							reject(e);
						}
					}
					function rejected(value) {
						try {
							step(generator["throw"](value));
						} catch (e) {
							reject(e);
						}
					}
					function step(result) {
						result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected);
					}
					step((generator = generator.apply(thisArg, _arguments || [])).next());
				});
			}
			function __generator(thisArg, body) {
				var _ = {
						label: 0,
						sent: function () {
							if (t[0] & 1) throw t[1];
							return t[1];
						},
						trys: [],
						ops: []
					},
					f,
					y,
					t,
					g;
				return g = {
					next: verb(0),
					"throw": verb(1),
					"return": verb(2)
				}, typeof Symbol === "function" && (g[Symbol.iterator] = function () {
					return this;
				}), g;
				function verb(n) {
					return function (v) {
						return step([n, v]);
					};
				}
				function step(op) {
					if (f) throw new TypeError("Generator is already executing.");
					while (g && (g = 0, op[0] && (_ = 0)), _) try {
						if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
						if (y = 0, t) op = [op[0] & 2, t.value];
						switch (op[0]) {
							case 0:
							case 1:
								t = op;
								break;
							case 4:
								_.label++;
								return {
									value: op[1],
									done: false
								};
							case 5:
								_.label++;
								y = op[1];
								op = [0];
								continue;
							case 7:
								op = _.ops.pop();
								_.trys.pop();
								continue;
							default:
								if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) {
									_ = 0;
									continue;
								}
								if (op[0] === 3 && (!t || op[1] > t[0] && op[1] < t[3])) {
									_.label = op[1];
									break;
								}
								if (op[0] === 6 && _.label < t[1]) {
									_.label = t[1];
									t = op;
									break;
								}
								if (t && _.label < t[2]) {
									_.label = t[2];
									_.ops.push(op);
									break;
								}
								if (t[2]) _.ops.pop();
								_.trys.pop();
								continue;
						}
						op = body.call(thisArg, _);
					} catch (e) {
						op = [6, e];
						y = 0;
					} finally {
						f = t = 0;
					}
					if (op[0] & 5) throw op[1];
					return {
						value: op[0] ? op[1] : void 0,
						done: true
					};
				}
			}
			function __spreadArray(to, from, pack) {
				if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
					if (ar || !(i in from)) {
						if (!ar) ar = Array.prototype.slice.call(from, 0, i);
						ar[i] = from[i];
					}
				}
				return to.concat(ar || Array.prototype.slice.call(from));
			}
			var commonjsGlobal$1 = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof commonjsGlobal !== 'undefined' ? commonjsGlobal : typeof self !== 'undefined' ? self : {};
			function createCommonjsModule(fn) {
				var module = {
					exports: {}
				};
				return fn(module, module.exports), module.exports;
			}
			var meilisearch_umd = createCommonjsModule(function (module, exports) {
				(function (global, factory) {
					factory(exports);
				})(commonjsGlobal$1, function (exports) {
					// Type definitions for meilisearch
					// Project: https://github.com/meilisearch/meilisearch-js
					// Definitions by: qdequele <quentin@meilisearch.com> <https://github.com/meilisearch>
					// Definitions: https://github.com/meilisearch/meilisearch-js
					// TypeScript Version: ^3.8.3

					/*
	         * SEARCH PARAMETERS
	         */
					var MatchingStrategies = {
						ALL: 'all',
						LAST: 'last'
					};
					var ContentTypeEnum = {
						JSON: 'application/json',
						CSV: 'text/csv',
						NDJSON: 'application/x-ndjson'
					};

					/******************************************************************************
					 Copyright (c) Microsoft Corporation.
					 Permission to use, copy, modify, and/or distribute this software for any
					 purpose with or without fee is hereby granted.
					 THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
					 REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
					 AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
					 INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
					 LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
					 OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
					 PERFORMANCE OF THIS SOFTWARE.
					 ***************************************************************************** */
					/* global Reflect, Promise */

					var extendStatics = function (d, b) {
						extendStatics = Object.setPrototypeOf || {
							__proto__: []
						} instanceof Array && function (d, b) {
							d.__proto__ = b;
						} || function (d, b) {
							for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p];
						};
						return extendStatics(d, b);
					};
					function __extends(d, b) {
						if (typeof b !== "function" && b !== null) throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
						extendStatics(d, b);
						function __() {
							this.constructor = d;
						}
						d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
					}
					var __assign = function () {
						__assign = Object.assign || function __assign(t) {
							for (var s, i = 1, n = arguments.length; i < n; i++) {
								s = arguments[i];
								for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
							}
							return t;
						};
						return __assign.apply(this, arguments);
					};
					function __awaiter(thisArg, _arguments, P, generator) {
						function adopt(value) {
							return value instanceof P ? value : new P(function (resolve) {
								resolve(value);
							});
						}
						return new (P || (P = Promise))(function (resolve, reject) {
							function fulfilled(value) {
								try {
									step(generator.next(value));
								} catch (e) {
									reject(e);
								}
							}
							function rejected(value) {
								try {
									step(generator["throw"](value));
								} catch (e) {
									reject(e);
								}
							}
							function step(result) {
								result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected);
							}
							step((generator = generator.apply(thisArg, _arguments || [])).next());
						});
					}
					function __generator(thisArg, body) {
						var _ = {
								label: 0,
								sent: function () {
									if (t[0] & 1) throw t[1];
									return t[1];
								},
								trys: [],
								ops: []
							},
							f,
							y,
							t,
							g;
						return g = {
							next: verb(0),
							"throw": verb(1),
							"return": verb(2)
						}, typeof Symbol === "function" && (g[Symbol.iterator] = function () {
							return this;
						}), g;
						function verb(n) {
							return function (v) {
								return step([n, v]);
							};
						}
						function step(op) {
							if (f) throw new TypeError("Generator is already executing.");
							while (_) try {
								if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
								if (y = 0, t) op = [op[0] & 2, t.value];
								switch (op[0]) {
									case 0:
									case 1:
										t = op;
										break;
									case 4:
										_.label++;
										return {
											value: op[1],
											done: false
										};
									case 5:
										_.label++;
										y = op[1];
										op = [0];
										continue;
									case 7:
										op = _.ops.pop();
										_.trys.pop();
										continue;
									default:
										if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) {
											_ = 0;
											continue;
										}
										if (op[0] === 3 && (!t || op[1] > t[0] && op[1] < t[3])) {
											_.label = op[1];
											break;
										}
										if (op[0] === 6 && _.label < t[1]) {
											_.label = t[1];
											t = op;
											break;
										}
										if (t && _.label < t[2]) {
											_.label = t[2];
											_.ops.push(op);
											break;
										}
										if (t[2]) _.ops.pop();
										_.trys.pop();
										continue;
								}
								op = body.call(thisArg, _);
							} catch (e) {
								op = [6, e];
								y = 0;
							} finally {
								f = t = 0;
							}
							if (op[0] & 5) throw op[1];
							return {
								value: op[0] ? op[1] : void 0,
								done: true
							};
						}
					}
					var MeiliSearchCommunicationError = /** @class */
						function (_super) {
							__extends(MeiliSearchCommunicationError, _super);
							function MeiliSearchCommunicationError(message, body, url, stack) {
								var _this = this;
								var _a, _b, _c;
								_this = _super.call(this, message) || this; // Make errors comparison possible. ex: error instanceof MeiliSearchCommunicationError.

								Object.setPrototypeOf(_this, MeiliSearchCommunicationError.prototype);
								_this.name = 'MeiliSearchCommunicationError';
								if (body instanceof Response) {
									_this.message = body.statusText;
									_this.statusCode = body.status;
								}
								if (body instanceof Error) {
									_this.errno = body.errno;
									_this.code = body.code;
								}
								if (stack) {
									_this.stack = stack;
									_this.stack = (_a = _this.stack) === null || _a === void 0 ? void 0 : _a.replace(/(TypeError|FetchError)/, _this.name);
									_this.stack = (_b = _this.stack) === null || _b === void 0 ? void 0 : _b.replace('Failed to fetch', "request to ".concat(url, " failed, reason: connect ECONNREFUSED"));
									_this.stack = (_c = _this.stack) === null || _c === void 0 ? void 0 : _c.replace('Not Found', "Not Found: ".concat(url));
								} else {
									if (Error.captureStackTrace) {
										Error.captureStackTrace(_this, MeiliSearchCommunicationError);
									}
								}
								return _this;
							}
							return MeiliSearchCommunicationError;
						}(Error);
					var MeiliSearchApiError = /** @class */
						function (_super) {
							__extends(class_1, _super);
							function class_1(error, status) {
								var _this = _super.call(this, error.message) || this; // Make errors comparison possible. ex: error instanceof MeiliSearchApiError.

								Object.setPrototypeOf(_this, MeiliSearchApiError.prototype);
								_this.name = 'MeiliSearchApiError';
								_this.code = error.code;
								_this.type = error.type;
								_this.link = error.link;
								_this.message = error.message;
								_this.httpStatus = status;
								if (Error.captureStackTrace) {
									Error.captureStackTrace(_this, MeiliSearchApiError);
								}
								return _this;
							}
							return class_1;
						}(Error);
					function httpResponseErrorHandler(response) {
						return __awaiter(this, void 0, void 0, function () {
							var responseBody;
							return __generator(this, function (_a) {
								switch (_a.label) {
									case 0:
										if (!!response.ok) return [3
											/*break*/, 5];
										responseBody = void 0;
										_a.label = 1;
									case 1:
										_a.trys.push([1, 3,, 4]);
										return [4
											/*yield*/, response.json()];
									case 2:
										// If it is not possible to parse the return body it means there is none
										// In which case it is a communication error with the Meilisearch instance
										responseBody = _a.sent();
										return [3
											/*break*/, 4];
									case 3:
										_a.sent(); // Not sure on how to test this part of the code.

										throw new MeiliSearchCommunicationError(response.statusText, response, response.url);
									case 4:
										// If the body is parsable, then it means Meilisearch returned a body with
										// information on the error.
										throw new MeiliSearchApiError(responseBody, response.status);
									case 5:
										return [2
											/*return*/, response];
								}
							});
						});
					}
					function httpErrorHandler(response, stack, url) {
						if (response.name !== 'MeiliSearchApiError') {
							throw new MeiliSearchCommunicationError(response.message, response, url, stack);
						}
						throw response;
					}
					var MeiliSearchError = /** @class */
						function (_super) {
							__extends(MeiliSearchError, _super);
							function MeiliSearchError(message) {
								var _this = _super.call(this, message) || this; // Make errors comparison possible. ex: error instanceof MeiliSearchError.

								Object.setPrototypeOf(_this, MeiliSearchError.prototype);
								_this.name = 'MeiliSearchError';
								if (Error.captureStackTrace) {
									Error.captureStackTrace(_this, MeiliSearchError);
								}
								return _this;
							}
							return MeiliSearchError;
						}(Error);
					var MeiliSearchTimeOutError = /** @class */
						function (_super) {
							__extends(MeiliSearchTimeOutError, _super);
							function MeiliSearchTimeOutError(message) {
								var _this = _super.call(this, message) || this; // Make errors comparison possible. ex: error instanceof MeiliSearchTimeOutError.

								Object.setPrototypeOf(_this, MeiliSearchTimeOutError.prototype);
								_this.name = 'MeiliSearchTimeOutError';
								if (Error.captureStackTrace) {
									Error.captureStackTrace(_this, MeiliSearchTimeOutError);
								}
								return _this;
							}
							return MeiliSearchTimeOutError;
						}(Error);
					function versionErrorHintMessage(message, method) {
						return "".concat(message, "\nHint: It might not be working because maybe you're not up to date with the Meilisearch version that ").concat(method, " call requires.");
					}
					function _typeof(obj) {
						"@babel/helpers - typeof";

						return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
							return typeof obj;
						} : function (obj) {
							return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
						}, _typeof(obj);
					}

					/** Removes undefined entries from object */

					function removeUndefinedFromObject(obj) {
						return Object.entries(obj).reduce(function (acc, curEntry) {
							var key = curEntry[0],
								val = curEntry[1];
							if (val !== undefined) acc[key] = val;
							return acc;
						}, {});
					}
					function sleep(ms) {
						return __awaiter(this, void 0, void 0, function () {
							return __generator(this, function (_a) {
								switch (_a.label) {
									case 0:
										return [4
											/*yield*/, new Promise(function (resolve) {
												return setTimeout(resolve, ms);
											})];
									case 1:
										return [2
											/*return*/, _a.sent()];
								}
							});
						});
					}
					function addProtocolIfNotPresent(host) {
						if (!(host.startsWith('https://') || host.startsWith('http://'))) {
							return "http://".concat(host);
						}
						return host;
					}
					function addTrailingSlash(url) {
						if (!url.endsWith('/')) {
							url += '/';
						}
						return url;
					}
					var PACKAGE_VERSION = '0.33.0';
					function toQueryParams(parameters) {
						var params = Object.keys(parameters);
						var queryParams = params.reduce(function (acc, key) {
							var _a, _b, _c;
							var value = parameters[key];
							if (value === undefined) {
								return acc;
							} else if (Array.isArray(value)) {
								return __assign(__assign({}, acc), (_a = {}, _a[key] = value.join(','), _a));
							} else if (value instanceof Date) {
								return __assign(__assign({}, acc), (_b = {}, _b[key] = value.toISOString(), _b));
							}
							return __assign(__assign({}, acc), (_c = {}, _c[key] = value, _c));
						}, {});
						return queryParams;
					}
					function constructHostURL(host) {
						try {
							host = addProtocolIfNotPresent(host);
							host = addTrailingSlash(host);
							return host;
						} catch (e) {
							throw new MeiliSearchError('The provided host is not valid.');
						}
					}
					function cloneAndParseHeaders(headers) {
						if (Array.isArray(headers)) {
							return headers.reduce(function (acc, headerPair) {
								acc[headerPair[0]] = headerPair[1];
								return acc;
							}, {});
						} else if ('has' in headers) {
							var clonedHeaders_1 = {};
							headers.forEach(function (value, key) {
								return clonedHeaders_1[key] = value;
							});
							return clonedHeaders_1;
						} else {
							return Object.assign({}, headers);
						}
					}
					function createHeaders(config) {
						var _a, _b;
						var agentHeader = 'X-Meilisearch-Client';
						var packageAgent = "Meilisearch JavaScript (v".concat(PACKAGE_VERSION, ")");
						var contentType = 'Content-Type';
						var authorization = 'Authorization';
						var headers = cloneAndParseHeaders((_b = (_a = config.requestConfig) === null || _a === void 0 ? void 0 : _a.headers) !== null && _b !== void 0 ? _b : {}); // do not override if user provided the header

						if (config.apiKey && !headers[authorization]) {
							headers[authorization] = "Bearer ".concat(config.apiKey);
						}
						if (!headers[contentType]) {
							headers['Content-Type'] = 'application/json';
						} // Creates the custom user agent with information on the package used.

						if (config.clientAgents && Array.isArray(config.clientAgents)) {
							var clients = config.clientAgents.concat(packageAgent);
							headers[agentHeader] = clients.join(' ; ');
						} else if (config.clientAgents && !Array.isArray(config.clientAgents)) {
							// If the header is defined but not an array
							throw new MeiliSearchError("Meilisearch: The header \"".concat(agentHeader, "\" should be an array of string(s).\n"));
						} else {
							headers[agentHeader] = packageAgent;
						}
						return headers;
					}
					var HttpRequests = /** @class */
						function () {
							function HttpRequests(config) {
								this.headers = createHeaders(config);
								this.requestConfig = config.requestConfig;
								this.httpClient = config.httpClient;
								try {
									var host = constructHostURL(config.host);
									this.url = new URL(host);
								} catch (e) {
									throw new MeiliSearchError('The provided host is not valid.');
								}
							}
							HttpRequests.prototype.request = function (_a) {
								var _b;
								var method = _a.method,
									url = _a.url,
									params = _a.params,
									body = _a.body,
									_c = _a.config,
									config = _c === void 0 ? {} : _c;
								return __awaiter(this, void 0, void 0, function () {
									var constructURL, queryParams_1, headers, fetchFn, result, response, parsedBody, e_1, stack;
									return __generator(this, function (_d) {
										switch (_d.label) {
											case 0:
												constructURL = new URL(url, this.url);
												if (params) {
													queryParams_1 = new URLSearchParams();
													Object.keys(params).filter(function (x) {
														return params[x] !== null;
													}).map(function (x) {
														return queryParams_1.set(x, params[x]);
													});
													constructURL.search = queryParams_1.toString();
												} // in case a custom content-type is provided
												// do not stringify body

												if (!((_b = config.headers) === null || _b === void 0 ? void 0 : _b['Content-Type'])) {
													body = JSON.stringify(body);
												}
												headers = __assign(__assign({}, this.headers), config.headers);
												_d.label = 1;
											case 1:
												_d.trys.push([1, 6,, 7]);
												fetchFn = this.httpClient ? this.httpClient : fetch;
												result = fetchFn(constructURL.toString(), __assign(__assign(__assign({}, config), this.requestConfig), {
													method: method,
													body: body,
													headers: headers
												}));
												if (!this.httpClient) return [3
													/*break*/, 3];
												return [4
													/*yield*/, result];
											case 2:
												return [2
													/*return*/, _d.sent()];
											case 3:
												return [4
													/*yield*/, result.then(function (res) {
														return httpResponseErrorHandler(res);
													})];
											case 4:
												response = _d.sent();
												return [4
													/*yield*/, response.json()["catch"](function () {
														return undefined;
													})];
											case 5:
												parsedBody = _d.sent();
												return [2
													/*return*/, parsedBody];
											case 6:
												e_1 = _d.sent();
												stack = e_1.stack;
												httpErrorHandler(e_1, stack, constructURL.toString());
												return [3
													/*break*/, 7];
											case 7:
												return [2
													/*return*/];
										}
									});
								});
							};

							HttpRequests.prototype.get = function (url, params, config) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.request({
														method: 'GET',
														url: url,
														params: params,
														config: config
													})];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							HttpRequests.prototype.post = function (url, data, params, config) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.request({
														method: 'POST',
														url: url,
														body: data,
														params: params,
														config: config
													})];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							HttpRequests.prototype.put = function (url, data, params, config) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.request({
														method: 'PUT',
														url: url,
														body: data,
														params: params,
														config: config
													})];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							HttpRequests.prototype.patch = function (url, data, params, config) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.request({
														method: 'PATCH',
														url: url,
														body: data,
														params: params,
														config: config
													})];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							HttpRequests.prototype["delete"] = function (url, data, params, config) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.request({
														method: 'DELETE',
														url: url,
														body: data,
														params: params,
														config: config
													})];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							return HttpRequests;
						}();
					var EnqueuedTask = /** @class */
						function () {
							function EnqueuedTask(task) {
								this.taskUid = task.taskUid;
								this.indexUid = task.indexUid;
								this.status = task.status;
								this.type = task.type;
								this.enqueuedAt = new Date(task.enqueuedAt);
							}
							return EnqueuedTask;
						}();
					var Task = /** @class */
						function () {
							function Task(task) {
								this.indexUid = task.indexUid;
								this.status = task.status;
								this.type = task.type;
								this.uid = task.uid;
								this.details = task.details;
								this.canceledBy = task.canceledBy;
								this.error = task.error;
								this.duration = task.duration;
								this.startedAt = new Date(task.startedAt);
								this.enqueuedAt = new Date(task.enqueuedAt);
								this.finishedAt = new Date(task.finishedAt);
							}
							return Task;
						}();
					var TaskClient = /** @class */
						function () {
							function TaskClient(config) {
								this.httpRequest = new HttpRequests(config);
							}
							/**
							 * Get one task
							 *
							 * @param uid - Unique identifier of the task
							 * @returns
							 */

							TaskClient.prototype.getTask = function (uid) {
								return __awaiter(this, void 0, void 0, function () {
									var url, taskItem;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "tasks/".concat(uid);
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												taskItem = _a.sent();
												return [2
													/*return*/, new Task(taskItem)];
										}
									});
								});
							};
							/**
							 * Get tasks
							 *
							 * @param parameters - Parameters to browse the tasks
							 * @returns Promise containing all tasks
							 */

							TaskClient.prototype.getTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, tasks;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "tasks";
												return [4
													/*yield*/, this.httpRequest.get(url, toQueryParams(parameters))];
											case 1:
												tasks = _a.sent();
												return [2
													/*return*/, __assign(__assign({}, tasks), {
														results: tasks.results.map(function (task) {
															return new Task(task);
														})
													})];
										}
									});
								});
							};
							/**
							 * Wait for a task to be processed.
							 *
							 * @param taskUid - Task identifier
							 * @param options - Additional configuration options
							 * @returns Promise returning a task after it has been processed
							 */

							TaskClient.prototype.waitForTask = function (taskUid, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									var startingTime, response;
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												startingTime = Date.now();
												_e.label = 1;
											case 1:
												if (!(Date.now() - startingTime < timeOutMs)) return [3
													/*break*/, 4];
												return [4
													/*yield*/, this.getTask(taskUid)];
											case 2:
												response = _e.sent();
												if (!["enqueued"
													/* TaskStatus.TASK_ENQUEUED */, "processing"
													/* TaskStatus.TASK_PROCESSING */].includes(response.status)) return [2
													/*return*/, response];
												return [4
													/*yield*/, sleep(intervalMs)];
											case 3:
												_e.sent();
												return [3
													/*break*/, 1];
											case 4:
												throw new MeiliSearchTimeOutError("timeout of ".concat(timeOutMs, "ms has exceeded on process ").concat(taskUid, " when waiting a task to be resolved."));
										}
									});
								});
							};
							/**
							 * Waits for multiple tasks to be processed
							 *
							 * @param taskUids - Tasks identifier list
							 * @param options - Wait options
							 * @returns Promise returning a list of tasks after they have been processed
							 */

							TaskClient.prototype.waitForTasks = function (taskUids, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									var tasks, _i, taskUids_1, taskUid, task;
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												tasks = [];
												_i = 0, taskUids_1 = taskUids;
												_e.label = 1;
											case 1:
												if (!(_i < taskUids_1.length)) return [3
													/*break*/, 4];
												taskUid = taskUids_1[_i];
												return [4
													/*yield*/, this.waitForTask(taskUid, {
														timeOutMs: timeOutMs,
														intervalMs: intervalMs
													})];
											case 2:
												task = _e.sent();
												tasks.push(task);
												_e.label = 3;
											case 3:
												_i++;
												return [3
													/*break*/, 1];
											case 4:
												return [2
													/*return*/, tasks];
										}
									});
								});
							};
							/**
							 * Cancel a list of enqueued or processing tasks.
							 *
							 * @param parameters - Parameters to filter the tasks.
							 * @returns Promise containing an EnqueuedTask
							 */

							TaskClient.prototype.cancelTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "tasks/cancel";
												return [4
													/*yield*/, this.httpRequest.post(url, {}, toQueryParams(parameters))];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Delete a list tasks.
							 *
							 * @param parameters - Parameters to filter the tasks.
							 * @returns Promise containing an EnqueuedTask
							 */

							TaskClient.prototype.deleteTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "tasks";
												return [4
													/*yield*/, this.httpRequest["delete"](url, {}, toQueryParams(parameters))];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							return TaskClient;
						}();

					/*
	         * Bundle: MeiliSearch / Indexes
	         * Project: MeiliSearch - Javascript API
	         * Author: Quentin de Quelen <quentin@meilisearch.com>
	         * Copyright: 2019, MeiliSearch
	         */

					var Index = /** @class */
						function () {
							/**
							 * @param config - Request configuration options
							 * @param uid - UID of the index
							 * @param primaryKey - Primary Key of the index
							 */
							function Index(config, uid, primaryKey) {
								this.uid = uid;
								this.primaryKey = primaryKey;
								this.httpRequest = new HttpRequests(config);
								this.tasks = new TaskClient(config);
							} ///
							/// SEARCH
							///

							/**
							 * Search for documents into an index
							 *
							 * @param query - Query string
							 * @param options - Search options
							 * @param config - Additional request configuration options
							 * @returns Promise containing the search response
							 */

							Index.prototype.search = function (query, options, config) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/search");
												return [4
													/*yield*/, this.httpRequest.post(url, removeUndefinedFromObject(__assign({
														q: query
													}, options)), undefined, config)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Search for documents into an index using the GET method
							 *
							 * @param query - Query string
							 * @param options - Search options
							 * @param config - Additional request configuration options
							 * @returns Promise containing the search response
							 */

							Index.prototype.searchGet = function (query, options, config) {
								var _a, _b, _c, _d, _e;
								return __awaiter(this, void 0, void 0, function () {
									var url, parseFilter, getParams;
									return __generator(this, function (_f) {
										switch (_f.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/search");
												parseFilter = function parseFilter(filter) {
													if (typeof filter === 'string') return filter;else if (Array.isArray(filter)) throw new MeiliSearchError('The filter query parameter should be in string format when using searchGet');else return undefined;
												};
												getParams = __assign(__assign({
													q: query
												}, options), {
													filter: parseFilter(options === null || options === void 0 ? void 0 : options.filter),
													sort: (_a = options === null || options === void 0 ? void 0 : options.sort) === null || _a === void 0 ? void 0 : _a.join(','),
													facets: (_b = options === null || options === void 0 ? void 0 : options.facets) === null || _b === void 0 ? void 0 : _b.join(','),
													attributesToRetrieve: (_c = options === null || options === void 0 ? void 0 : options.attributesToRetrieve) === null || _c === void 0 ? void 0 : _c.join(','),
													attributesToCrop: (_d = options === null || options === void 0 ? void 0 : options.attributesToCrop) === null || _d === void 0 ? void 0 : _d.join(','),
													attributesToHighlight: (_e = options === null || options === void 0 ? void 0 : options.attributesToHighlight) === null || _e === void 0 ? void 0 : _e.join(',')
												});
												return [4
													/*yield*/, this.httpRequest.get(url, removeUndefinedFromObject(getParams), config)];
											case 1:
												return [2
													/*return*/, _f.sent()];
										}
									});
								});
							}; ///
							/// INDEX
							///

							/**
							 * Get index information.
							 *
							 * @returns Promise containing index information
							 */

							Index.prototype.getRawInfo = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, res;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid);
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												res = _a.sent();
												this.primaryKey = res.primaryKey;
												this.updatedAt = new Date(res.updatedAt);
												this.createdAt = new Date(res.createdAt);
												return [2
													/*return*/, res];
										}
									});
								});
							};
							/**
							 * Fetch and update Index information.
							 *
							 * @returns Promise to the current Index object with updated information
							 */

							Index.prototype.fetchInfo = function () {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.getRawInfo()];
											case 1:
												_a.sent();
												return [2
													/*return*/, this];
										}
									});
								});
							};
							/**
							 * Get Primary Key.
							 *
							 * @returns Promise containing the Primary Key of the index
							 */

							Index.prototype.fetchPrimaryKey = function () {
								return __awaiter(this, void 0, void 0, function () {
									var _a;
									return __generator(this, function (_b) {
										switch (_b.label) {
											case 0:
												_a = this;
												return [4
													/*yield*/, this.getRawInfo()];
											case 1:
												_a.primaryKey = _b.sent().primaryKey;
												return [2
													/*return*/, this.primaryKey];
										}
									});
								});
							};
							/**
							 * Create an index.
							 *
							 * @param uid - Unique identifier of the Index
							 * @param options - Index options
							 * @param config - Request configuration options
							 * @returns Newly created Index object
							 */

							Index.create = function (uid, options, config) {
								if (options === void 0) {
									options = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, req, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes";
												req = new HttpRequests(config);
												return [4
													/*yield*/, req.post(url, __assign(__assign({}, options), {
														uid: uid
													}))];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Update an index.
							 *
							 * @param data - Data to update
							 * @returns Promise to the current Index object with updated information
							 */

							Index.prototype.update = function (data) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid);
												return [4
													/*yield*/, this.httpRequest.patch(url, data)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							};
							/**
							 * Delete an index.
							 *
							 * @returns Promise which resolves when index is deleted successfully
							 */

							Index.prototype["delete"] = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid);
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							}; ///
							/// TASKS
							///

							/**
							 * Get the list of all the tasks of the index.
							 *
							 * @param parameters - Parameters to browse the tasks
							 * @returns Promise containing all tasks
							 */

							Index.prototype.getTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.getTasks(__assign(__assign({}, parameters), {
														indexUids: [this.uid]
													}))];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Get one task of the index.
							 *
							 * @param taskUid - Task identifier
							 * @returns Promise containing a task
							 */

							Index.prototype.getTask = function (taskUid) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.getTask(taskUid)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Wait for multiple tasks to be processed.
							 *
							 * @param taskUids - Tasks identifier
							 * @param waitOptions - Options on timeout and interval
							 * @returns Promise containing an array of tasks
							 */

							Index.prototype.waitForTasks = function (taskUids, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.waitForTasks(taskUids, {
														timeOutMs: timeOutMs,
														intervalMs: intervalMs
													})];
											case 1:
												return [2
													/*return*/, _e.sent()];
										}
									});
								});
							};
							/**
							 * Wait for a task to be processed.
							 *
							 * @param taskUid - Task identifier
							 * @param waitOptions - Options on timeout and interval
							 * @returns Promise containing an array of tasks
							 */

							Index.prototype.waitForTask = function (taskUid, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.waitForTask(taskUid, {
														timeOutMs: timeOutMs,
														intervalMs: intervalMs
													})];
											case 1:
												return [2
													/*return*/, _e.sent()];
										}
									});
								});
							}; ///
							/// STATS
							///

							/**
							 * Get stats of an index
							 *
							 * @returns Promise containing object with stats of the index
							 */

							Index.prototype.getStats = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/stats");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// DOCUMENTS
							///

							/**
							 * Get documents of an index.
							 *
							 * @param parameters - Parameters to browse the documents. Parameters can
							 *   contain the `filter` field only available in Meilisearch v1.2 and newer
							 * @returns Promise containing the returned documents
							 */

							Index.prototype.getDocuments = function (parameters) {
								var _a;
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, e_1, url, fields;
									return __generator(this, function (_b) {
										switch (_b.label) {
											case 0:
												parameters = removeUndefinedFromObject(parameters);
												if (!(parameters.filter !== undefined)) return [3
													/*break*/, 5];
												_b.label = 1;
											case 1:
												_b.trys.push([1, 3,, 4]);
												url = "indexes/".concat(this.uid, "/documents/fetch");
												return [4
													/*yield*/, this.httpRequest.post(url, parameters)];
											case 2:
												return [2
													/*return*/, _b.sent()];
											case 3:
												e_1 = _b.sent();
												if (e_1 instanceof MeiliSearchCommunicationError) {
													e_1.message = versionErrorHintMessage(e_1.message, 'getDocuments');
												} else if (e_1 instanceof MeiliSearchApiError) {
													e_1.message = versionErrorHintMessage(e_1.message, 'getDocuments');
												}
												throw e_1;
											case 4:
												return [3
													/*break*/, 7];
											case 5:
												url = "indexes/".concat(this.uid, "/documents");
												fields = Array.isArray(parameters === null || parameters === void 0 ? void 0 : parameters.fields) ? {
													fields: (_a = parameters === null || parameters === void 0 ? void 0 : parameters.fields) === null || _a === void 0 ? void 0 : _a.join(',')
												} : {};
												return [4
													/*yield*/, this.httpRequest.get(url, __assign(__assign({}, parameters), fields))];
											case 6:
												return [2
													/*return*/, _b.sent()];
											case 7:
												return [2
													/*return*/];
										}
									});
								});
							};
							/**
							 * Get one document
							 *
							 * @param documentId - Document ID
							 * @param parameters - Parameters applied on a document
							 * @returns Promise containing Document response
							 */

							Index.prototype.getDocument = function (documentId, parameters) {
								return __awaiter(this, void 0, void 0, function () {
									var url, fields;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents/").concat(documentId);
												fields = function () {
													var _a;
													if (Array.isArray(parameters === null || parameters === void 0 ? void 0 : parameters.fields)) {
														return (_a = parameters === null || parameters === void 0 ? void 0 : parameters.fields) === null || _a === void 0 ? void 0 : _a.join(',');
													}
													return undefined;
												}();
												return [4
													/*yield*/, this.httpRequest.get(url, removeUndefinedFromObject(__assign(__assign({}, parameters), {
														fields: fields
													})))];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Add or replace multiples documents to an index
							 *
							 * @param documents - Array of Document objects to add/replace
							 * @param options - Options on document addition
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.addDocuments = function (documents, options) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents");
												return [4
													/*yield*/, this.httpRequest.post(url, documents, options)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Add or replace multiples documents in a string format to an index. It only
							 * supports csv, ndjson and json formats.
							 *
							 * @param documents - Documents provided in a string to add/replace
							 * @param contentType - Content type of your document:
							 *   'text/csv'|'application/x-ndjson'|'application/json'
							 * @param options - Options on document addition
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.addDocumentsFromString = function (documents, contentType, queryParams) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents");
												return [4
													/*yield*/, this.httpRequest.post(url, documents, queryParams, {
														headers: {
															'Content-Type': contentType
														}
													})];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Add or replace multiples documents to an index in batches
							 *
							 * @param documents - Array of Document objects to add/replace
							 * @param batchSize - Size of the batch
							 * @param options - Options on document addition
							 * @returns Promise containing array of enqueued task objects for each batch
							 */

							Index.prototype.addDocumentsInBatches = function (documents, batchSize, options) {
								if (batchSize === void 0) {
									batchSize = 1000;
								}
								return __awaiter(this, void 0, void 0, function () {
									var updates, i, _a, _b;
									return __generator(this, function (_c) {
										switch (_c.label) {
											case 0:
												updates = [];
												i = 0;
												_c.label = 1;
											case 1:
												if (!(i < documents.length)) return [3
													/*break*/, 4];
												_b = (_a = updates).push;
												return [4
													/*yield*/, this.addDocuments(documents.slice(i, i + batchSize), options)];
											case 2:
												_b.apply(_a, [_c.sent()]);
												_c.label = 3;
											case 3:
												i += batchSize;
												return [3
													/*break*/, 1];
											case 4:
												return [2
													/*return*/, updates];
										}
									});
								});
							};
							/**
							 * Add or update multiples documents to an index
							 *
							 * @param documents - Array of Document objects to add/update
							 * @param options - Options on document update
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateDocuments = function (documents, options) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents");
												return [4
													/*yield*/, this.httpRequest.put(url, documents, options)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Add or update multiples documents to an index in batches
							 *
							 * @param documents - Array of Document objects to add/update
							 * @param batchSize - Size of the batch
							 * @param options - Options on document update
							 * @returns Promise containing array of enqueued task objects for each batch
							 */

							Index.prototype.updateDocumentsInBatches = function (documents, batchSize, options) {
								if (batchSize === void 0) {
									batchSize = 1000;
								}
								return __awaiter(this, void 0, void 0, function () {
									var updates, i, _a, _b;
									return __generator(this, function (_c) {
										switch (_c.label) {
											case 0:
												updates = [];
												i = 0;
												_c.label = 1;
											case 1:
												if (!(i < documents.length)) return [3
													/*break*/, 4];
												_b = (_a = updates).push;
												return [4
													/*yield*/, this.updateDocuments(documents.slice(i, i + batchSize), options)];
											case 2:
												_b.apply(_a, [_c.sent()]);
												_c.label = 3;
											case 3:
												i += batchSize;
												return [3
													/*break*/, 1];
											case 4:
												return [2
													/*return*/, updates];
										}
									});
								});
							};
							/**
							 * Add or update multiples documents in a string format to an index. It only
							 * supports csv, ndjson and json formats.
							 *
							 * @param documents - Documents provided in a string to add/update
							 * @param contentType - Content type of your document:
							 *   'text/csv'|'application/x-ndjson'|'application/json'
							 * @param queryParams - Options on raw document addition
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateDocumentsFromString = function (documents, contentType, queryParams) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents");
												return [4
													/*yield*/, this.httpRequest.put(url, documents, queryParams, {
														headers: {
															'Content-Type': contentType
														}
													})];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Delete one document
							 *
							 * @param documentId - Id of Document to delete
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.deleteDocument = function (documentId) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents/").concat(documentId);
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							};
							/**
							 * Delete multiples documents of an index.
							 *
							 * @param params - Params value can be:
							 *
							 *   - DocumentsDeletionQuery: An object containing the parameters to customize
							 *       your document deletion. Only available in Meilisearch v1.2 and newer
							 *   - DocumentsIds: An array of document ids to delete
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.deleteDocuments = function (params) {
								return __awaiter(this, void 0, void 0, function () {
									var isDocumentsDeletionQuery, endpoint, url, task, e_2;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												isDocumentsDeletionQuery = !Array.isArray(params) && _typeof(params) === 'object';
												endpoint = isDocumentsDeletionQuery ? 'documents/delete' : 'documents/delete-batch';
												url = "indexes/".concat(this.uid, "/").concat(endpoint);
												_a.label = 1;
											case 1:
												_a.trys.push([1, 3,, 4]);
												return [4
													/*yield*/, this.httpRequest.post(url, params)];
											case 2:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
											case 3:
												e_2 = _a.sent();
												if (e_2 instanceof MeiliSearchCommunicationError && isDocumentsDeletionQuery) {
													e_2.message = versionErrorHintMessage(e_2.message, 'deleteDocuments');
												} else if (e_2 instanceof MeiliSearchApiError) {
													e_2.message = versionErrorHintMessage(e_2.message, 'deleteDocuments');
												}
												throw e_2;
											case 4:
												return [2
													/*return*/];
										}
									});
								});
							};
							/**
							 * Delete all documents of an index
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.deleteAllDocuments = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/documents");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// SETTINGS
							///

							/**
							 * Retrieve all settings
							 *
							 * @returns Promise containing Settings object
							 */

							Index.prototype.getSettings = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update all settings Any parameters not provided will be left unchanged.
							 *
							 * @param settings - Object containing parameters with their updated values
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateSettings = function (settings) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings");
												return [4
													/*yield*/, this.httpRequest.patch(url, settings)];
											case 1:
												task = _a.sent();
												task.enqueued = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							};
							/**
							 * Reset settings.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetSettings = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// PAGINATION SETTINGS
							///

							/**
							 * Get the pagination settings.
							 *
							 * @returns Promise containing object of pagination settings
							 */

							Index.prototype.getPagination = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/pagination");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the pagination settings.
							 *
							 * @param pagination - Pagination object
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updatePagination = function (pagination) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/pagination");
												return [4
													/*yield*/, this.httpRequest.patch(url, pagination)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the pagination settings.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetPagination = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/pagination");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							}; ///
							/// SYNONYMS
							///

							/**
							 * Get the list of all synonyms
							 *
							 * @returns Promise containing object of synonym mappings
							 */

							Index.prototype.getSynonyms = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/synonyms");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the list of synonyms. Overwrite the old list.
							 *
							 * @param synonyms - Mapping of synonyms with their associated words
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateSynonyms = function (synonyms) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/synonyms");
												return [4
													/*yield*/, this.httpRequest.put(url, synonyms)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the synonym list to be empty again
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetSynonyms = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/synonyms");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// STOP WORDS
							///

							/**
							 * Get the list of all stop-words
							 *
							 * @returns Promise containing array of stop-words
							 */

							Index.prototype.getStopWords = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/stop-words");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the list of stop-words. Overwrite the old list.
							 *
							 * @param stopWords - Array of strings that contains the stop-words.
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateStopWords = function (stopWords) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/stop-words");
												return [4
													/*yield*/, this.httpRequest.put(url, stopWords)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the stop-words list to be empty again
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetStopWords = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/stop-words");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// RANKING RULES
							///

							/**
							 * Get the list of all ranking-rules
							 *
							 * @returns Promise containing array of ranking-rules
							 */

							Index.prototype.getRankingRules = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/ranking-rules");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the list of ranking-rules. Overwrite the old list.
							 *
							 * @param rankingRules - Array that contain ranking rules sorted by order of
							 *   importance.
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateRankingRules = function (rankingRules) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/ranking-rules");
												return [4
													/*yield*/, this.httpRequest.put(url, rankingRules)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the ranking rules list to its default value
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetRankingRules = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/ranking-rules");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// DISTINCT ATTRIBUTE
							///

							/**
							 * Get the distinct-attribute
							 *
							 * @returns Promise containing the distinct-attribute of the index
							 */

							Index.prototype.getDistinctAttribute = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the distinct-attribute.
							 *
							 * @param distinctAttribute - Field name of the distinct-attribute
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateDistinctAttribute = function (distinctAttribute) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
												return [4
													/*yield*/, this.httpRequest.put(url, distinctAttribute)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the distinct-attribute.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetDistinctAttribute = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/distinct-attribute");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// FILTERABLE ATTRIBUTES
							///

							/**
							 * Get the filterable-attributes
							 *
							 * @returns Promise containing an array of filterable-attributes
							 */

							Index.prototype.getFilterableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the filterable-attributes.
							 *
							 * @param filterableAttributes - Array of strings containing the attributes
							 *   that can be used as filters at query time
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateFilterableAttributes = function (filterableAttributes) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
												return [4
													/*yield*/, this.httpRequest.put(url, filterableAttributes)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the filterable-attributes.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetFilterableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/filterable-attributes");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// SORTABLE ATTRIBUTES
							///

							/**
							 * Get the sortable-attributes
							 *
							 * @returns Promise containing array of sortable-attributes
							 */

							Index.prototype.getSortableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the sortable-attributes.
							 *
							 * @param sortableAttributes - Array of strings containing the attributes that
							 *   can be used to sort search results at query time
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateSortableAttributes = function (sortableAttributes) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
												return [4
													/*yield*/, this.httpRequest.put(url, sortableAttributes)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the sortable-attributes.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetSortableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/sortable-attributes");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// SEARCHABLE ATTRIBUTE
							///

							/**
							 * Get the searchable-attributes
							 *
							 * @returns Promise containing array of searchable-attributes
							 */

							Index.prototype.getSearchableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the searchable-attributes.
							 *
							 * @param searchableAttributes - Array of strings that contains searchable
							 *   attributes sorted by order of importance(most to least important)
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateSearchableAttributes = function (searchableAttributes) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
												return [4
													/*yield*/, this.httpRequest.put(url, searchableAttributes)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the searchable-attributes.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetSearchableAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/searchable-attributes");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// DISPLAYED ATTRIBUTE
							///

							/**
							 * Get the displayed-attributes
							 *
							 * @returns Promise containing array of displayed-attributes
							 */

							Index.prototype.getDisplayedAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the displayed-attributes.
							 *
							 * @param displayedAttributes - Array of strings that contains attributes of
							 *   an index to display
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateDisplayedAttributes = function (displayedAttributes) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
												return [4
													/*yield*/, this.httpRequest.put(url, displayedAttributes)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the displayed-attributes.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetDisplayedAttributes = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/displayed-attributes");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// TYPO TOLERANCE
							///

							/**
							 * Get the typo tolerance settings.
							 *
							 * @returns Promise containing the typo tolerance settings.
							 */

							Index.prototype.getTypoTolerance = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the typo tolerance settings.
							 *
							 * @param typoTolerance - Object containing the custom typo tolerance
							 *   settings.
							 * @returns Promise containing object of the enqueued update
							 */

							Index.prototype.updateTypoTolerance = function (typoTolerance) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
												return [4
													/*yield*/, this.httpRequest.patch(url, typoTolerance)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							};
							/**
							 * Reset the typo tolerance settings.
							 *
							 * @returns Promise containing object of the enqueued update
							 */

							Index.prototype.resetTypoTolerance = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/typo-tolerance");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												task.enqueuedAt = new Date(task.enqueuedAt);
												return [2
													/*return*/, task];
										}
									});
								});
							}; ///
							/// FACETING
							///

							/**
							 * Get the faceting settings.
							 *
							 * @returns Promise containing object of faceting index settings
							 */

							Index.prototype.getFaceting = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/faceting");
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update the faceting settings.
							 *
							 * @param faceting - Faceting index settings object
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.updateFaceting = function (faceting) {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/faceting");
												return [4
													/*yield*/, this.httpRequest.patch(url, faceting)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							/**
							 * Reset the faceting settings.
							 *
							 * @returns Promise containing an EnqueuedTask
							 */

							Index.prototype.resetFaceting = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes/".concat(this.uid, "/settings/faceting");
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							};
							return Index;
						}();

					/*
	         * Bundle: MeiliSearch
	         * Project: MeiliSearch - Javascript API
	         * Author: Quentin de Quelen <quentin@meilisearch.com>
	         * Copyright: 2019, MeiliSearch
	         */

					var Client = /** @class */
						function () {
							/**
							 * Creates new MeiliSearch instance
							 *
							 * @param config - Configuration object
							 */
							function Client(config) {
								this.config = config;
								this.httpRequest = new HttpRequests(config);
								this.tasks = new TaskClient(config);
							}
							/**
							 * Return an Index instance
							 *
							 * @param indexUid - The index UID
							 * @returns Instance of Index
							 */

							Client.prototype.index = function (indexUid) {
								return new Index(this.config, indexUid);
							};
							/**
							 * Gather information about an index by calling MeiliSearch and return an
							 * Index instance with the gathered information
							 *
							 * @param indexUid - The index UID
							 * @returns Promise returning Index instance
							 */

							Client.prototype.getIndex = function (indexUid) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										return [2
											/*return*/, new Index(this.config, indexUid).fetchInfo()];
									});
								});
							};
							/**
							 * Gather information about an index by calling MeiliSearch and return the raw
							 * JSON response
							 *
							 * @param indexUid - The index UID
							 * @returns Promise returning index information
							 */

							Client.prototype.getRawIndex = function (indexUid) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										return [2
											/*return*/, new Index(this.config, indexUid).getRawInfo()];
									});
								});
							};
							/**
							 * Get all the indexes as Index instances.
							 *
							 * @param parameters - Parameters to browse the indexes
							 * @returns Promise returning array of raw index information
							 */

							Client.prototype.getIndexes = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var rawIndexes, indexes;
									var _this = this;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.getRawIndexes(parameters)];
											case 1:
												rawIndexes = _a.sent();
												indexes = rawIndexes.results.map(function (index) {
													return new Index(_this.config, index.uid, index.primaryKey);
												});
												return [2
													/*return*/, __assign(__assign({}, rawIndexes), {
														results: indexes
													})];
										}
									});
								});
							};
							/**
							 * Get all the indexes in their raw value (no Index instances).
							 *
							 * @param parameters - Parameters to browse the indexes
							 * @returns Promise returning array of raw index information
							 */

							Client.prototype.getRawIndexes = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "indexes";
												return [4
													/*yield*/, this.httpRequest.get(url, parameters)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Create a new index
							 *
							 * @param uid - The index UID
							 * @param options - Index options
							 * @returns Promise returning Index instance
							 */

							Client.prototype.createIndex = function (uid, options) {
								if (options === void 0) {
									options = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, Index.create(uid, options, this.config)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update an index
							 *
							 * @param uid - The index UID
							 * @param options - Index options to update
							 * @returns Promise returning Index instance after updating
							 */

							Client.prototype.updateIndex = function (uid, options) {
								if (options === void 0) {
									options = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, new Index(this.config, uid).update(options)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Delete an index
							 *
							 * @param uid - The index UID
							 * @returns Promise which resolves when index is deleted successfully
							 */

							Client.prototype.deleteIndex = function (uid) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, new Index(this.config, uid)["delete"]()];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Deletes an index if it already exists.
							 *
							 * @param uid - The index UID
							 * @returns Promise which resolves to true when index exists and is deleted
							 *   successfully, otherwise false if it does not exist
							 */

							Client.prototype.deleteIndexIfExists = function (uid) {
								return __awaiter(this, void 0, void 0, function () {
									var e_1;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												_a.trys.push([0, 2,, 3]);
												return [4
													/*yield*/, this.deleteIndex(uid)];
											case 1:
												_a.sent();
												return [2
													/*return*/, true];
											case 2:
												e_1 = _a.sent();
												if (e_1.code === "index_not_found"
													/* ErrorStatusCode.INDEX_NOT_FOUND */) {
													return [2
														/*return*/, false];
												}
												throw e_1;
											case 3:
												return [2
													/*return*/];
										}
									});
								});
							};
							/**
							 * Swaps a list of index tuples.
							 *
							 * @param params - List of indexes tuples to swap.
							 * @returns Promise returning object of the enqueued task
							 */

							Client.prototype.swapIndexes = function (params) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = '/swap-indexes';
												return [4
													/*yield*/, this.httpRequest.post(url, params)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// Multi Search
							///

							/**
							 * Perform multiple search queries.
							 *
							 * It is possible to make multiple search queries on the same index or on
							 * different ones
							 *
							 * @example
							 *
							 * ```ts
							 * client.multiSearch({
							 *   queries: [
							 *     { indexUid: 'movies', q: 'wonder' },
							 *     { indexUid: 'books', q: 'flower' },
							 *   ],
							 * })
							 * ```
							 *
							 * @param queries - Search queries
							 * @param config - Additional request configuration options
							 * @returns Promise containing the search responses
							 */

							Client.prototype.multiSearch = function (queries, config) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "multi-search";
												return [4
													/*yield*/, this.httpRequest.post(url, queries, undefined, config)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// TASKS
							///

							/**
							 * Get the list of all client tasks
							 *
							 * @param parameters - Parameters to browse the tasks
							 * @returns Promise returning all tasks
							 */

							Client.prototype.getTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.getTasks(parameters)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Get one task on the client scope
							 *
							 * @param taskUid - Task identifier
							 * @returns Promise returning a task
							 */

							Client.prototype.getTask = function (taskUid) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.getTask(taskUid)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Wait for multiple tasks to be finished.
							 *
							 * @param taskUids - Tasks identifier
							 * @param waitOptions - Options on timeout and interval
							 * @returns Promise returning an array of tasks
							 */

							Client.prototype.waitForTasks = function (taskUids, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.waitForTasks(taskUids, {
														timeOutMs: timeOutMs,
														intervalMs: intervalMs
													})];
											case 1:
												return [2
													/*return*/, _e.sent()];
										}
									});
								});
							};
							/**
							 * Wait for a task to be finished.
							 *
							 * @param taskUid - Task identifier
							 * @param waitOptions - Options on timeout and interval
							 * @returns Promise returning an array of tasks
							 */

							Client.prototype.waitForTask = function (taskUid, _a) {
								var _b = _a === void 0 ? {} : _a,
									_c = _b.timeOutMs,
									timeOutMs = _c === void 0 ? 5000 : _c,
									_d = _b.intervalMs,
									intervalMs = _d === void 0 ? 50 : _d;
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_e) {
										switch (_e.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.waitForTask(taskUid, {
														timeOutMs: timeOutMs,
														intervalMs: intervalMs
													})];
											case 1:
												return [2
													/*return*/, _e.sent()];
										}
									});
								});
							};
							/**
							 * Cancel a list of enqueued or processing tasks.
							 *
							 * @param parameters - Parameters to filter the tasks.
							 * @returns Promise containing an EnqueuedTask
							 */

							Client.prototype.cancelTasks = function (parameters) {
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.cancelTasks(parameters)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Delete a list of tasks.
							 *
							 * @param parameters - Parameters to filter the tasks.
							 * @returns Promise containing an EnqueuedTask
							 */

							Client.prototype.deleteTasks = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												return [4
													/*yield*/, this.tasks.deleteTasks(parameters)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// KEYS
							///

							/**
							 * Get all API keys
							 *
							 * @param parameters - Parameters to browse the indexes
							 * @returns Promise returning an object with keys
							 */

							Client.prototype.getKeys = function (parameters) {
								if (parameters === void 0) {
									parameters = {};
								}
								return __awaiter(this, void 0, void 0, function () {
									var url, keys;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "keys";
												return [4
													/*yield*/, this.httpRequest.get(url, parameters)];
											case 1:
												keys = _a.sent();
												keys.results = keys.results.map(function (key) {
													return __assign(__assign({}, key), {
														createdAt: new Date(key.createdAt),
														updateAt: new Date(key.updateAt)
													});
												});
												return [2
													/*return*/, keys];
										}
									});
								});
							};
							/**
							 * Get one API key
							 *
							 * @param keyOrUid - Key or uid of the API key
							 * @returns Promise returning a key
							 */

							Client.prototype.getKey = function (keyOrUid) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "keys/".concat(keyOrUid);
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Create one API key
							 *
							 * @param options - Key options
							 * @returns Promise returning a key
							 */

							Client.prototype.createKey = function (options) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "keys";
												return [4
													/*yield*/, this.httpRequest.post(url, options)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Update one API key
							 *
							 * @param keyOrUid - Key
							 * @param options - Key options
							 * @returns Promise returning a key
							 */

							Client.prototype.updateKey = function (keyOrUid, options) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "keys/".concat(keyOrUid);
												return [4
													/*yield*/, this.httpRequest.patch(url, options)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Delete one API key
							 *
							 * @param keyOrUid - Key
							 * @returns
							 */

							Client.prototype.deleteKey = function (keyOrUid) {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "keys/".concat(keyOrUid);
												return [4
													/*yield*/, this.httpRequest["delete"](url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// HEALTH
							///

							/**
							 * Checks if the server is healthy, otherwise an error will be thrown.
							 *
							 * @returns Promise returning an object with health details
							 */

							Client.prototype.health = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "health";
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							};
							/**
							 * Checks if the server is healthy, return true or false.
							 *
							 * @returns Promise returning a boolean
							 */

							Client.prototype.isHealthy = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												_a.trys.push([0, 2,, 3]);
												url = "health";
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												_a.sent();
												return [2
													/*return*/, true];
											case 2:
												_a.sent();
												return [2
													/*return*/, false];
											case 3:
												return [2
													/*return*/];
										}
									});
								});
							}; ///
							/// STATS
							///

							/**
							 * Get the stats of all the database
							 *
							 * @returns Promise returning object of all the stats
							 */

							Client.prototype.getStats = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "stats";
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// VERSION
							///

							/**
							 * Get the version of MeiliSearch
							 *
							 * @returns Promise returning object with version details
							 */

							Client.prototype.getVersion = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "version";
												return [4
													/*yield*/, this.httpRequest.get(url)];
											case 1:
												return [2
													/*return*/, _a.sent()];
										}
									});
								});
							}; ///
							/// DUMPS
							///

							/**
							 * Creates a dump
							 *
							 * @returns Promise returning object of the enqueued task
							 */

							Client.prototype.createDump = function () {
								return __awaiter(this, void 0, void 0, function () {
									var url, task;
									return __generator(this, function (_a) {
										switch (_a.label) {
											case 0:
												url = "dumps";
												return [4
													/*yield*/, this.httpRequest.post(url)];
											case 1:
												task = _a.sent();
												return [2
													/*return*/, new EnqueuedTask(task)];
										}
									});
								});
							}; ///
							/// TOKENS
							///

							/**
							 * Generate a tenant token
							 *
							 * @param apiKeyUid - The uid of the api key used as issuer of the token.
							 * @param searchRules - Search rules that are applied to every search.
							 * @param options - Token options to customize some aspect of the token.
							 * @returns The token in JWT format.
							 */

							Client.prototype.generateTenantToken = function (_apiKeyUid, _searchRules, _options) {
								var error = new Error();
								throw new Error("Meilisearch: failed to generate a tenant token. Generation of a token only works in a node environment \n ".concat(error.stack, "."));
							};
							return Client;
						}();
					var MeiliSearch = /** @class */
						function (_super) {
							__extends(MeiliSearch, _super);
							function MeiliSearch(config) {
								return _super.call(this, config) || this;
							}
							return MeiliSearch;
						}(Client);
					exports.ContentTypeEnum = ContentTypeEnum;
					exports.Index = Index;
					exports.MatchingStrategies = MatchingStrategies;
					exports.MeiliSearch = MeiliSearch;
					exports.MeiliSearchApiError = MeiliSearchApiError;
					exports.MeiliSearchCommunicationError = MeiliSearchCommunicationError;
					exports.MeiliSearchError = MeiliSearchError;
					exports.MeiliSearchTimeOutError = MeiliSearchTimeOutError;
					exports["default"] = MeiliSearch;
					exports.httpErrorHandler = httpErrorHandler;
					exports.httpResponseErrorHandler = httpResponseErrorHandler;
					exports.versionErrorHintMessage = versionErrorHintMessage;
					Object.defineProperty(exports, '__esModule', {
						value: true
					});
				});
			});
			function isPureObject(data) {
				return typeof data === 'object' && !Array.isArray(data) && data !== null;
			}

			/**
			 * Get the configuration of instant meilisearch
			 *
			 * @param {InstantMeiliSearchOptions} option
			 * @returns {InstantMeiliSearchConfig}
			 */
			function getInstantMeilisearchConfig(options) {
				var defaultOptions = {
					placeholderSearch: true,
					keepZeroFacets: false,
					clientAgents: [],
					finitePagination: false
				};
				return __assign(__assign({}, defaultOptions), options);
			}
			/**
			 * Resolves apiKey if it is a function
			 * @param  {string | apiKeyCallback} apiKey
			 * @returns {string} api key value
			 */
			function getApiKey(apiKey) {
				// If apiKey is function, call it to get the apiKey
				if (typeof apiKey === 'function') {
					var apiKeyFnValue = apiKey();
					if (typeof apiKeyFnValue !== 'string') {
						throw new TypeError('Provided apiKey function (2nd parameter) did not return a string, expected string');
					}
					return apiKeyFnValue;
				}
				return apiKey;
			}
			/**
			 * Validates host and apiKey parameters, throws if invalid
			 * @param hostUrl
			 * @param apiKey
			 */
			function validateInstantMeiliSearchParams(hostUrl, apiKey, instantMeiliSearchOptions) {
				var requestConfig = instantMeiliSearchOptions.requestConfig,
					httpClient = instantMeiliSearchOptions.httpClient;
				// Validate host url
				if (typeof hostUrl !== 'string') {
					throw new TypeError('Provided hostUrl value (1st parameter) is not a string, expected string');
				}
				// Validate api key
				if (typeof apiKey !== 'string' && typeof apiKey !== 'function') {
					throw new TypeError('Provided apiKey value (2nd parameter) is not a string or a function, expected string or function');
				}
				// Validate requestConfig
				if (requestConfig !== undefined && !isPureObject(requestConfig)) {
					throw new TypeError('Provided requestConfig should be an object');
				}
				// Validate custom HTTP client
				if (httpClient && typeof httpClient !== 'function') {
					throw new TypeError('Provided custom httpClient should be a function');
				}
			}

			/**
			 * @param  {ResponseCacher} cache
			 */
			function SearchResolver(client, cache) {
				return {
					multiSearch: function (searchQueries, instantSearchPagination) {
						return __awaiter(this, void 0, void 0, function () {
							var key, cachedResponse, searchResponses, responseWithPagination;
							return __generator(this, function (_a) {
								switch (_a.label) {
									case 0:
										key = cache.formatKey([searchQueries]);
										cachedResponse = cache.getEntry(key);
										// Check if specific request is already cached with its associated search response.
										if (cachedResponse) return [2 /*return*/, cachedResponse];
										return [4 /*yield*/, client.multiSearch({
											queries: searchQueries
										})];
									case 1:
										searchResponses = _a.sent();
										responseWithPagination = searchResponses.results.map(function (response, index) {
											return __assign(__assign({}, response), {
												// TODO: should be removed at one point
												pagination: instantSearchPagination[index] || {}
											});
										});
										// Cache response
										cache.setEntry(key, responseWithPagination);
										return [2 /*return*/, responseWithPagination];
								}
							});
						});
					}
				};
			}
			function adaptGeoSearch(_a) {
				var insideBoundingBox = _a.insideBoundingBox,
					aroundLatLng = _a.aroundLatLng,
					aroundRadius = _a.aroundRadius,
					minimumAroundRadius = _a.minimumAroundRadius;
				var middlePoint;
				var radius;
				var filter;
				if (aroundLatLng) {
					var _b = aroundLatLng.split(',').map(function (pt) {
							return Number.parseFloat(pt).toFixed(5);
						}),
						lat = _b[0],
						lng = _b[1];
					middlePoint = [lat, lng];
				}
				if (aroundRadius != null || minimumAroundRadius != null) {
					if (aroundRadius === 'all') {
						console.warn('instant-meilisearch is not compatible with the `all` value on the aroundRadius parameter');
					} else if (aroundRadius != null) {
						radius = aroundRadius;
					} else {
						radius = minimumAroundRadius;
					}
				}
				if (insideBoundingBox && typeof insideBoundingBox === 'string') {
					var _c = insideBoundingBox.split(',').map(function (pt) {
							return parseFloat(pt);
						}),
						lat1 = _c[0],
						lng1 = _c[1],
						lat2 = _c[2],
						lng2 = _c[3];
					filter = "_geoBoundingBox([".concat(lat1, ", ").concat(lng1, "], [").concat(lat2, ", ").concat(lng2, "])");
				} else if (middlePoint != null && radius != null) {
					var lat = middlePoint[0],
						lng = middlePoint[1];
					filter = "_geoRadius(".concat(lat, ", ").concat(lng, ", ").concat(radius, ")");
				}
				return filter;
			}

			/**
			 * Transform InstantSearch filter to Meilisearch compatible filter format.
			 * Change sign from `:` to `=`
			 * "facet:facetValue" becomes "facet=facetValue"
			 *
			 * Wrap both the facet and its facet value between quotes.
			 * This avoid formating issues on facets containing multiple words.
			 *
			 * 'My facet:My facet value' becomes '"My facet":"My facet value"'
			 *
			 * @param  {string} filter?
			 * @returns {Filter}
			 */
			function transformFilter(filter) {
				return filter.replace(/(.*):(.*)/i, '"$1"="$2"');
			}
			/**
			 * Itterate over all filters.
			 * Return the filters in a Meilisearch compatible format.
			 *
			 * @param  {SearchContext['facetFilters']} filters?
			 * @returns {Filter}
			 */
			function transformFilters(filters) {
				if (typeof filters === 'string') {
					return transformFilter(filters);
				} else if (Array.isArray(filters)) return filters.map(function (filter) {
					if (Array.isArray(filter)) return filter.map(function (nestedFilter) {
						return transformFilter(nestedFilter);
					}).filter(function (elem) {
						return elem;
					});else {
						return transformFilter(filter);
					}
				}).filter(function (elem) {
					return elem;
				});
				return [];
			}
			/**
			 * Return the filter in an array if it is a string
			 * If filter is array, return without change.
			 *
			 * @param  {Filter} filter
			 * @returns {Array}
			 */
			function filterToArray(filter) {
				// Filter is a string
				if (filter === '') return [];else if (typeof filter === 'string') return [filter];
				// Filter is either an array of strings, or an array of array of strings
				return filter;
			}
			/**
			 * Merge facetFilters, numericFilters and filters together.
			 *
			 * @param  {Filter} facetFilters
			 * @param  {Filter} numericFilters
			 * @param  {string} filters
			 * @returns {Filter}
			 */
			function mergeFilters(facetFilters, numericFilters, filters) {
				var adaptedFilters = filters.trim();
				var adaptedFacetFilters = filterToArray(facetFilters);
				var adaptedNumericFilters = filterToArray(numericFilters);
				var adaptedFilter = __spreadArray(__spreadArray(__spreadArray([], adaptedFacetFilters, true), adaptedNumericFilters, true), [adaptedFilters], false);
				var cleanedFilters = adaptedFilter.filter(function (filter) {
					if (Array.isArray(filter)) {
						return filter.length;
					}
					return filter;
				});
				return cleanedFilters;
			}
			/**
			 * Adapt instantsearch.js filters to Meilisearch filters by
			 * combining and transforming all provided filters.
			 *
			 * @param  {string|undefined} filters
			 * @param  {SearchContext['numericFilters']} numericFilters
			 * @param  {SearchContext['facetFilters']} facetFilters
			 * @returns {Filter}
			 */
			function adaptFilters(filters, numericFilters, facetFilters) {
				var transformedFilter = transformFilters(facetFilters || []);
				var transformedNumericFilter = transformFilters(numericFilters || []);
				return mergeFilters(transformedFilter, transformedNumericFilter, filters || '');
			}
			function isPaginationRequired(filter, query, placeholderSearch) {
				// To disable pagination:
				// placeholderSearch must be disabled
				// The search query must be empty
				// There must be no filters
				if (!placeholderSearch && !query && (!filter || filter.length === 0)) {
					return false;
				}
				return true;
			}
			function setScrollPagination(pagination, paginationRequired) {
				var page = pagination.page,
					hitsPerPage = pagination.hitsPerPage;
				if (!paginationRequired) {
					return {
						limit: 0,
						offset: 0
					};
				}
				return {
					limit: hitsPerPage + 1,
					offset: page * hitsPerPage
				};
			}
			function setFinitePagination(pagination, paginationRequired) {
				var page = pagination.page,
					hitsPerPage = pagination.hitsPerPage;
				if (!paginationRequired) {
					return {
						hitsPerPage: 0,
						page: page + 1
					};
				} else {
					return {
						hitsPerPage: hitsPerPage,
						page: page + 1
					};
				}
			}
			/**
			 * Adapts instantsearch.js and instant-meilisearch options
			 * to meilisearch search query parameters.
			 *
			 * @param  {SearchContext} searchContext
			 */
			function MeiliParamsCreator(searchContext) {
				var meiliSearchParams = {};
				var facets = searchContext.facets,
					attributesToSnippet = searchContext.attributesToSnippet,
					snippetEllipsisText = searchContext.snippetEllipsisText,
					attributesToRetrieve = searchContext.attributesToRetrieve,
					attributesToHighlight = searchContext.attributesToHighlight,
					highlightPreTag = searchContext.highlightPreTag,
					highlightPostTag = searchContext.highlightPostTag,
					placeholderSearch = searchContext.placeholderSearch,
					query = searchContext.query,
					sort = searchContext.sort,
					pagination = searchContext.pagination,
					matchingStrategy = searchContext.matchingStrategy,
					filters = searchContext.filters,
					numericFilters = searchContext.numericFilters,
					facetFilters = searchContext.facetFilters,
					indexUid = searchContext.indexUid;
				var meilisearchFilters = adaptFilters(filters, numericFilters, facetFilters);
				return {
					getParams: function () {
						return meiliSearchParams;
					},
					addQuery: function () {
						meiliSearchParams.q = query;
					},
					addIndexUid: function () {
						meiliSearchParams.indexUid = indexUid;
					},
					addFacets: function () {
						if (Array.isArray(facets)) {
							meiliSearchParams.facets = facets;
						} else if (typeof facets === 'string') {
							meiliSearchParams.facets = [facets];
						}
					},
					addAttributesToCrop: function () {
						if (attributesToSnippet) {
							meiliSearchParams.attributesToCrop = attributesToSnippet;
						}
					},
					addCropMarker: function () {
						// Attributes To Crop marker
						if (snippetEllipsisText != null) {
							meiliSearchParams.cropMarker = snippetEllipsisText;
						}
					},
					addAttributesToRetrieve: function () {
						if (attributesToRetrieve) {
							meiliSearchParams.attributesToRetrieve = attributesToRetrieve;
						}
					},
					addFilters: function () {
						if (meilisearchFilters.length) {
							meiliSearchParams.filter = meilisearchFilters;
						}
					},
					addAttributesToHighlight: function () {
						meiliSearchParams.attributesToHighlight = attributesToHighlight || ['*'];
					},
					addPreTag: function () {
						if (highlightPreTag) {
							meiliSearchParams.highlightPreTag = highlightPreTag;
						} else {
							meiliSearchParams.highlightPreTag = '__ais-highlight__';
						}
					},
					addPostTag: function () {
						if (highlightPostTag) {
							meiliSearchParams.highlightPostTag = highlightPostTag;
						} else {
							meiliSearchParams.highlightPostTag = '__/ais-highlight__';
						}
					},
					addPagination: function () {
						var paginationRequired = isPaginationRequired(meilisearchFilters, query, placeholderSearch);
						if (pagination.finite) {
							var _a = setFinitePagination(pagination, paginationRequired),
								hitsPerPage = _a.hitsPerPage,
								page = _a.page;
							meiliSearchParams.hitsPerPage = hitsPerPage;
							meiliSearchParams.page = page;
						} else {
							var _b = setScrollPagination(pagination, paginationRequired),
								limit = _b.limit,
								offset = _b.offset;
							meiliSearchParams.limit = limit;
							meiliSearchParams.offset = offset;
						}
					},
					addSort: function () {
						if (sort === null || sort === void 0 ? void 0 : sort.length) {
							meiliSearchParams.sort = Array.isArray(sort) ? sort : [sort];
						}
					},
					addGeoSearchFilter: function () {
						var insideBoundingBox = searchContext.insideBoundingBox,
							aroundLatLng = searchContext.aroundLatLng,
							aroundRadius = searchContext.aroundRadius,
							minimumAroundRadius = searchContext.minimumAroundRadius;
						var filter = adaptGeoSearch({
							insideBoundingBox: insideBoundingBox,
							aroundLatLng: aroundLatLng,
							aroundRadius: aroundRadius,
							minimumAroundRadius: minimumAroundRadius
						});
						if (filter) {
							if (meiliSearchParams.filter) {
								meiliSearchParams.filter.unshift(filter);
							} else {
								meiliSearchParams.filter = [filter];
							}
						}
					},
					addMatchingStrategy: function () {
						if (matchingStrategy) {
							meiliSearchParams.matchingStrategy = matchingStrategy;
						}
					}
				};
			}
			/**
			 * Adapt search request from instantsearch.js
			 * to search request compliant with Meilisearch
			 *
			 * @param  {SearchContext} searchContext
			 * @returns {MeiliSearchMultiSearchParams}
			 */
			function adaptSearchParams(searchContext) {
				var meilisearchParams = MeiliParamsCreator(searchContext);
				meilisearchParams.addQuery();
				meilisearchParams.addIndexUid();
				meilisearchParams.addFacets();
				meilisearchParams.addAttributesToHighlight();
				meilisearchParams.addPreTag();
				meilisearchParams.addPostTag();
				meilisearchParams.addAttributesToRetrieve();
				meilisearchParams.addAttributesToCrop();
				meilisearchParams.addCropMarker();
				meilisearchParams.addPagination();
				meilisearchParams.addFilters();
				meilisearchParams.addSort();
				meilisearchParams.addGeoSearchFilter();
				meilisearchParams.addMatchingStrategy();
				return meilisearchParams.getParams();
			}
			function removeDuplicate(key) {
				var indexes = [];
				return function (object) {
					if (indexes.includes(object[key])) {
						return false;
					}
					indexes.push(object[key]);
					return true;
				};
			}

			/**
			 * @param  {any} str
			 * @returns {boolean}
			 */
			/**
			 * @param  {any[]} arr
			 * @returns {string}
			 */
			function stringifyArray(arr) {
				return arr.reduce(function (acc, curr) {
					return acc += JSON.stringify(curr);
				}, '');
			}

			/**
			 * Stringify values following instantsearch practices.
			 *
			 * @param  {any} value - value that needs to be stringified
			 */
			function stringifyValue(value) {
				if (typeof value === 'string') {
					// String
					return value;
				} else if (value === undefined) {
					// undefined
					return JSON.stringify(null);
				} else {
					return JSON.stringify(value);
				}
			}
			/**
			 * Recursif function wrap the deepest possible value
			 * the following way: { value: "xx" }.
			 *
			 * For example:
			 *
			 * {
			 * "rootField": { "value": "x" }
			 * "nestedField": { child: { value: "y" } }
			 * }
			 *
			 * recursivity continues until the value is not an array or an object.
			 *
			 * @param  {any} value - value of a field
			 *
			 * @returns Record<string, any>
			 */
			function wrapValue(value) {
				if (Array.isArray(value)) {
					// Array
					return value.map(function (elem) {
						return wrapValue(elem);
					});
				} else if (isPureObject(value)) {
					// Object
					return Object.keys(value).reduce(function (nested, key) {
						nested[key] = wrapValue(value[key]);
						return nested;
					}, {});
				} else {
					return {
						value: stringifyValue(value)
					};
				}
			}
			/**
			 * Adapt Meilisearch formatted fields to a format compliant to instantsearch.js.
			 *
			 * @param  {Record<string} formattedHit
			 * @param  {SearchContext} searchContext
			 * @returns {Record}
			 */
			function adaptFormattedFields(hit) {
				if (!hit) return {};
				var _formattedResult = wrapValue(hit);
				var highlightedHit = {
					// We could not determine what the differences are between those two fields.
					_highlightResult: _formattedResult,
					_snippetResult: _formattedResult
				};
				return highlightedHit;
			}

			/**
			 * @param  {any[]} hits
			 * @returns {Array<Record<string, any>>}
			 */
			function adaptGeoResponse(hits) {
				var _a;
				for (var i = 0; i < hits.length; i++) {
					var objectID = "".concat(i + Math.random() * 1000000);
					if (hits[i]._geo) {
						hits[i]._geoloc = hits[i]._geo;
						hits[i].objectID = objectID;
					}
					if ((_a = hits[i]._formatted) === null || _a === void 0 ? void 0 : _a._geo) {
						hits[i]._formatted._geoloc = hits[i]._formatted._geo;
						hits[i]._formatted.objectID = objectID;
					}
				}
				return hits;
			}

			/**
			 * @param  {MeilisearchMultiSearchResult} searchResult
			 * @param  {SearchContext} searchContext
			 * @returns {Array<Record<string, any>>}
			 */
			function adaptHits(searchResponse, config) {
				var hits = searchResponse.hits;
				var hitsPerPage = searchResponse.pagination.hitsPerPage;
				var finitePagination = config.finitePagination,
					primaryKey = config.primaryKey; // Needs: finite, hitsPerPage
				// if the length of the hits is bigger than the hitsPerPage
				// It means that there is still pages to come as we append limit by hitsPerPage + 1
				// In which case we still need to remove the additional hit returned by Meilisearch
				if (!finitePagination && hits.length > hitsPerPage) {
					hits.splice(hits.length - 1, 1);
				}
				var adaptedHits = hits.map(function (hit) {
					// Creates Hit object compliant with InstantSearch
					if (Object.keys(hit).length > 0) {
						var formattedHit = hit._formatted;
						hit._matchesPosition;
						var documentFields = __rest(hit, ["_formatted", "_matchesPosition"]);
						var adaptedHit = Object.assign(documentFields, adaptFormattedFields(formattedHit));
						if (primaryKey) {
							adaptedHit.objectID = hit[primaryKey];
						}
						return adaptedHit;
					}
					return hit;
				});
				adaptedHits = adaptGeoResponse(adaptedHits);
				return adaptedHits;
			}
			function adaptTotalHits(searchResponse) {
				var _a = searchResponse.hitsPerPage,
					hitsPerPage = _a === void 0 ? 0 : _a,
					_b = searchResponse.totalPages,
					totalPages = _b === void 0 ? 0 : _b,
					estimatedTotalHits = searchResponse.estimatedTotalHits,
					totalHits = searchResponse.totalHits;
				if (estimatedTotalHits != null) {
					return estimatedTotalHits;
				} else if (totalHits != null) {
					return totalHits;
				}
				// Should not happen but safeguarding just in case
				return hitsPerPage * totalPages;
			}
			function adaptNbPages(searchResponse, hitsPerPage) {
				if (searchResponse.totalPages != null) {
					return searchResponse.totalPages;
				}
				// Avoid dividing by 0
				if (hitsPerPage === 0) {
					return 0;
				}
				var _a = searchResponse.limit,
					limit = _a === void 0 ? 20 : _a,
					_b = searchResponse.offset,
					offset = _b === void 0 ? 0 : _b,
					hits = searchResponse.hits;
				var additionalPage = hits.length >= limit ? 1 : 0;
				return offset / hitsPerPage + 1 + additionalPage;
			}
			function adaptPaginationParameters(searchResponse, paginationState) {
				var hitsPerPage = paginationState.hitsPerPage,
					page = paginationState.page;
				var nbPages = adaptNbPages(searchResponse, hitsPerPage);
				return {
					page: page,
					nbPages: nbPages,
					hitsPerPage: hitsPerPage
				};
			}
			function getFacetNames(facets) {
				if (!facets) return [];else if (typeof facets === 'string') return [facets];
				return facets;
			}
			// Fills the missing facetValue in the current facet distribution if `keepZeroFacet` is true
			// using the initial facet distribution. Ex:
			//
			// Initial distribution: { genres: { horror: 10, comedy: 4 } }
			// Current distribution: { genres: { horror: 3 }}
			// Returned distribution: { genres: { horror: 3, comedy: 0 }}
			function fillMissingFacetValues(facets, initialFacetDistribution, facetDistribution) {
				var facetNames = getFacetNames(facets);
				var filledDistribution = {};
				for (var _i = 0, facetNames_1 = facetNames; _i < facetNames_1.length; _i++) {
					var facet = facetNames_1[_i];
					for (var facetValue in initialFacetDistribution[facet]) {
						if (!filledDistribution[facet]) {
							// initialize sub object
							filledDistribution[facet] = facetDistribution[facet] || {};
						}
						if (!filledDistribution[facet][facetValue]) {
							filledDistribution[facet][facetValue] = 0;
						} else {
							filledDistribution[facet][facetValue] = facetDistribution[facet][facetValue];
						}
					}
				}
				return filledDistribution;
			}
			function adaptFacetDistribution(keepZeroFacets, facets, initialFacetDistribution, facetDistribution) {
				if (keepZeroFacets) {
					facetDistribution = facetDistribution || {};
					return fillMissingFacetValues(facets, initialFacetDistribution, facetDistribution);
				}
				return facetDistribution;
			}
			function adaptFacetStats(meiliFacetStats) {
				var facetStats = Object.keys(meiliFacetStats).reduce(function (stats, facet) {
					stats[facet] = __assign(__assign({}, meiliFacetStats[facet]), {
						avg: 0,
						sum: 0
					}); // Set at 0 as these numbers are not provided by Meilisearch
					return stats;
				}, {});
				return facetStats;
			}

			/**
			 * Adapt multiple search results from Meilisearch
			 * to search results compliant with instantsearch.js
			 *
			 * @param  {Array<MeilisearchMultiSearchResult<T>>} searchResponse
			 * @param  {Record<string, FacetDistribution>} initialFacetDistribution
			 * @param  {InstantMeiliSearchConfig} config
			 * @returns {{ results: Array<AlgoliaSearchResponse<T>> }}
			 */
			function adaptSearchResults(meilisearchResults, initialFacetDistribution, config) {
				var instantSearchResult = meilisearchResults.map(function (meilisearchResult) {
					return adaptSearchResult(meilisearchResult, initialFacetDistribution[meilisearchResult.indexUid], config);
				});
				return {
					results: instantSearchResult
				};
			}
			/**
			 * Adapt search result from Meilisearch
			 * to search result compliant with instantsearch.js
			 *
			 * @param  {MeilisearchMultiSearchResult<Record<string>>} searchResponse
			 * @param  {Record<string, FacetDistribution>} initialFacetDistribution
			 * @param  {InstantMeiliSearchConfig} config
			 * @returns {AlgoliaSearchResponse<T>}
			 */
			function adaptSearchResult(meiliSearchResult, initialFacetDistribution, config) {
				var processingTimeMs = meiliSearchResult.processingTimeMs,
					query = meiliSearchResult.query,
					indexUid = meiliSearchResult.indexUid,
					_a = meiliSearchResult.facetDistribution,
					responseFacetDistribution = _a === void 0 ? {} : _a,
					_b = meiliSearchResult.facetStats,
					facetStats = _b === void 0 ? {} : _b;
				var facets = Object.keys(responseFacetDistribution);
				var _c = adaptPaginationParameters(meiliSearchResult, meiliSearchResult.pagination),
					hitsPerPage = _c.hitsPerPage,
					page = _c.page,
					nbPages = _c.nbPages;
				var hits = adaptHits(meiliSearchResult, config);
				var nbHits = adaptTotalHits(meiliSearchResult);
				var facetDistribution = adaptFacetDistribution(config.keepZeroFacets, facets, initialFacetDistribution, responseFacetDistribution);
				// Create result object compliant with InstantSearch
				var adaptedSearchResult = {
					index: indexUid,
					hitsPerPage: hitsPerPage,
					page: page,
					facets: facetDistribution,
					nbPages: nbPages,
					nbHits: nbHits,
					processingTimeMS: processingTimeMs,
					query: query,
					hits: hits,
					params: '',
					exhaustiveNbHits: false,
					facets_stats: adaptFacetStats(facetStats)
				};
				return adaptedSearchResult;
			}

			/**
			 * Create the current state of the pagination
			 *
			 * @param  {boolean} [finite]
			 * @param  {number} [hitsPerPage]
			 * @param  {number} [page]
			 * @returns {SearchContext}
			 */
			function createPaginationState(finite, hitsPerPage, page) {
				return {
					hitsPerPage: hitsPerPage === undefined ? 20 : hitsPerPage,
					page: page || 0,
					finite: !!finite
				};
			}

			/**
			 * @param {string} rawSort
			 * @returns {string[]}
			 */
			function createSortState(rawSort) {
				return rawSort.split(',').map(function (sort) {
					return sort.trim();
				}).filter(function (sort) {
					return !!sort;
				});
			}

			/**
			 * @param  {AlgoliaMultipleQueriesQuery} searchRequest
			 * @param  {Context} options
			 * @returns {SearchContext}
			 */
			function createSearchContext(searchRequest, options) {
				// Split index name and possible sorting rules
				var _a = searchRequest.indexName.split(':'),
					indexUid = _a[0],
					sortByArray = _a.slice(1);
				var query = searchRequest.query,
					instantSearchParams = searchRequest.params;
				var paginationState = createPaginationState(options.finitePagination, instantSearchParams === null || instantSearchParams === void 0 ? void 0 : instantSearchParams.hitsPerPage, instantSearchParams === null || instantSearchParams === void 0 ? void 0 : instantSearchParams.page);
				var sortState = createSortState(sortByArray.join(':'));
				var searchContext = __assign(__assign(__assign(__assign({}, options), {
					query: query
				}), instantSearchParams), {
					sort: sortState,
					indexUid: indexUid,
					pagination: paginationState,
					placeholderSearch: options.placeholderSearch !== false,
					keepZeroFacets: !!options.keepZeroFacets
				});
				return searchContext;
			}

			/**
			 * @param  {Record<string} cache
			 * @returns {SearchCache}
			 */
			function SearchCache(cache) {
				if (cache === void 0) {
					cache = {};
				}
				var searchCache = cache;
				return {
					getEntry: function (key) {
						if (searchCache[key]) {
							try {
								return JSON.parse(searchCache[key]);
							} catch (_) {
								return undefined;
							}
						}
						return undefined;
					},
					formatKey: function (components) {
						return stringifyArray(components);
					},
					setEntry: function (key, searchResponse) {
						searchCache[key] = JSON.stringify(searchResponse);
					},
					clearCache: function () {
						searchCache = {};
					}
				};
			}
			function getParametersWithoutFilters(searchContext) {
				var defaultSearchContext = __assign(__assign({}, searchContext), {
					// placeholdersearch true to ensure a request is made
					placeholderSearch: true,
					// query set to empty to ensure retrieving the default facetdistribution
					query: ''
				});
				var meilisearchParams = MeiliParamsCreator(defaultSearchContext);
				meilisearchParams.addFacets();
				meilisearchParams.addIndexUid();
				meilisearchParams.addPagination();
				return meilisearchParams.getParams();
			}
			// Fetch the initial facets distribution of an Index
			// Used to show the facets when `placeholderSearch` is set to true
			// Used to fill the missing facet values when `keepZeroFacets` is set to true
			function initFacetDistribution(searchResolver, queries, initialFacetDistribution) {
				return __awaiter(this, void 0, void 0, function () {
					var removeIndexUidDuplicates, searchQueries, results, _i, results_1, searchResult;
					return __generator(this, function (_a) {
						switch (_a.label) {
							case 0:
								removeIndexUidDuplicates = removeDuplicate('indexUid');
								searchQueries = queries.filter(removeIndexUidDuplicates) // only make one request per indexUid
									.filter(function (_a) {
										var indexUid = _a.indexUid;
										// avoid requesting on indexes that already have an initial facetDistribution
										return !Object.keys(initialFacetDistribution).includes(indexUid);
									});
								if (searchQueries.length === 0) return [2 /*return*/, initialFacetDistribution];
								return [4 /*yield*/, searchResolver.multiSearch(searchQueries, [])];
							case 1:
								results = _a.sent();
								for (_i = 0, results_1 = results; _i < results_1.length; _i++) {
									searchResult = results_1[_i];
									initialFacetDistribution[searchResult.indexUid] = searchResult.facetDistribution || {};
								}
								return [2 /*return*/, initialFacetDistribution];
						}
					});
				});
			}
			var PACKAGE_VERSION = '0.13.2';
			var constructClientAgents = function (clientAgents) {
				if (clientAgents === void 0) {
					clientAgents = [];
				}
				var instantMeilisearchAgent = "Meilisearch instant-meilisearch (v".concat(PACKAGE_VERSION, ")");
				return clientAgents.concat(instantMeilisearchAgent);
			};

			/**
			 * Instantiate SearchClient required by instantsearch.js.
			 * @param  {string} hostUrl
			 * @param  {string | apiKeyCallback} apiKey
			 * @param  {InstantMeiliSearchOptions={}} meiliSearchOptions
			 * @returns {InstantMeiliSearchInstance}
			 */
			function instantMeiliSearch(hostUrl, apiKey, instantMeiliSearchOptions) {
				if (apiKey === void 0) {
					apiKey = '';
				}
				if (instantMeiliSearchOptions === void 0) {
					instantMeiliSearchOptions = {};
				}
				// Validate parameters
				validateInstantMeiliSearchParams(hostUrl, apiKey, instantMeiliSearchOptions);
				// Resolve possible function to get apiKey
				apiKey = getApiKey(apiKey);
				var clientAgents = constructClientAgents(instantMeiliSearchOptions.clientAgents);
				var meilisearchConfig = {
					host: hostUrl,
					apiKey: apiKey,
					clientAgents: clientAgents
				};
				if (instantMeiliSearchOptions.httpClient !== undefined) {
					meilisearchConfig.httpClient = instantMeiliSearchOptions.httpClient;
				}
				if (instantMeiliSearchOptions.requestConfig !== undefined) {
					meilisearchConfig.requestConfig = instantMeiliSearchOptions.requestConfig;
				}
				var meilisearchClient = new meilisearch_umd.MeiliSearch(meilisearchConfig);
				var searchCache = SearchCache();
				// create search resolver with included cache
				var searchResolver = SearchResolver(meilisearchClient, searchCache);
				var initialFacetDistribution = {};
				var instantMeilisearchConfig = getInstantMeilisearchConfig(instantMeiliSearchOptions);
				return {
					clearCache: function () {
						return searchCache.clearCache();
					},
					/**
					 * @param  {readonlyAlgoliaMultipleQueriesQuery[]} instantSearchRequests
					 * @returns {Array}
					 */
					search: function (instantSearchRequests) {
						return __awaiter(this, void 0, void 0, function () {
							var meilisearchRequests, instantSearchPagination, initialFacetDistributionsRequests, _i, instantSearchRequests_1, searchRequest, searchContext, meilisearchSearchQuery, defaultSearchQuery, meilisearchResults, instantSearchResponse, e_1;
							return __generator(this, function (_a) {
								switch (_a.label) {
									case 0:
										_a.trys.push([0, 3,, 4]);
										meilisearchRequests = [];
										instantSearchPagination = [];
										initialFacetDistributionsRequests = [];
										for (_i = 0, instantSearchRequests_1 = instantSearchRequests; _i < instantSearchRequests_1.length; _i++) {
											searchRequest = instantSearchRequests_1[_i];
											searchContext = createSearchContext(searchRequest, instantMeiliSearchOptions);
											meilisearchSearchQuery = adaptSearchParams(searchContext);
											meilisearchRequests.push(meilisearchSearchQuery);
											defaultSearchQuery = getParametersWithoutFilters(searchContext);
											initialFacetDistributionsRequests.push(defaultSearchQuery);
											// Keep information about the pagination parameters of instantsearch as
											// they are needed to adapt the search response of Meilisearch
											instantSearchPagination.push(searchContext.pagination);
										}
										return [4 /*yield*/, initFacetDistribution(searchResolver, initialFacetDistributionsRequests, initialFacetDistribution)
											// Search request to Meilisearch happens here
										];

									case 1:
										initialFacetDistribution = _a.sent();
										return [4 /*yield*/, searchResolver.multiSearch(meilisearchRequests, instantSearchPagination // Create issue on pagination
										)];

									case 2:
										meilisearchResults = _a.sent();
										instantSearchResponse = adaptSearchResults(meilisearchResults, initialFacetDistribution, instantMeilisearchConfig);
										return [2 /*return*/, instantSearchResponse];
									case 3:
										e_1 = _a.sent();
										console.error(e_1);
										throw new Error(e_1);
									case 4:
										return [2 /*return*/];
								}
							});
						});
					},

					searchForFacetValues: function (_) {
						return __awaiter(this, void 0, void 0, function () {
							return __generator(this, function (_a) {
								switch (_a.label) {
									case 0:
										return [4 /*yield*/, new Promise(function (resolve, reject) {
											reject(new Error('SearchForFacetValues is not compatible with Meilisearch'));
											resolve([]); // added here to avoid compilation error
										})];

									case 1:
										return [2 /*return*/, _a.sent()];
								}
							});
						});
					}
				};
			}
			exports.MatchingStrategies = void 0;
			(function (MatchingStrategies) {
				MatchingStrategies["ALL"] = "all";
				MatchingStrategies["LAST"] = "last";
			})(exports.MatchingStrategies || (exports.MatchingStrategies = {}));
			exports.instantMeiliSearch = instantMeiliSearch;
			Object.defineProperty(exports, '__esModule', {
				value: true
			});
		});
	});

	var PACKAGE_VERSION = '0.2.1';

	/******************************************************************************
	 Copyright (c) Microsoft Corporation.

	 Permission to use, copy, modify, and/or distribute this software for any
	 purpose with or without fee is hereby granted.

	 THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
	 REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
	 AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
	 INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
	 LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
	 OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
	 PERFORMANCE OF THIS SOFTWARE.
	 ***************************************************************************** */

	var __assign = function() {
		__assign = Object.assign || function __assign(t) {
			for (var s, i = 1, n = arguments.length; i < n; i++) {
				s = arguments[i];
				for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
			}
			return t;
		};
		return __assign.apply(this, arguments);
	};

	function __rest(s, e) {
		var t = {};
		for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
			t[p] = s[p];
		if (s != null && typeof Object.getOwnPropertySymbols === "function")
			for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
				if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
					t[p[i]] = s[p[i]];
			}
		return t;
	}

	function __spreadArray(to, from, pack) {
		if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
			if (ar || !(i in from)) {
				if (!ar) ar = Array.prototype.slice.call(from, 0, i);
				ar[i] = from[i];
			}
		}
		return to.concat(ar || Array.prototype.slice.call(from));
	}

	var concatUserAgents = function (clientAgents) {
		return clientAgents.concat(clientAgents.filter(function (agent) { return agent; }));
	};
	/**
	 * Create a searchClient instance
	 */
	function createSearchClient(_a) {
		var userAgent = _a.userAgent;
		return function (_a) {
			var url = _a.url, apiKey = _a.apiKey, _b = _a.options, options = _b === void 0 ? { clientAgents: [] } : _b;
			var clientAgents = options.clientAgents || [];
			var searchClient = instantMeilisearch_umd.instantMeiliSearch(url, apiKey, __assign(__assign({}, options), { clientAgents: concatUserAgents(__spreadArray([userAgent], clientAgents, true)) }));
			return __assign({}, searchClient);
		};
	}

	/**
	 * Create searchClient instance for autocomplete
	 */
	var userAgent = "Meilisearch autocomplete-client (v".concat(PACKAGE_VERSION, ")");
	var meilisearchAutocompleteClient = createSearchClient({ userAgent: userAgent });

	var HIGHLIGHT_PRE_TAG = '__aa-highlight__';
	var HIGHLIGHT_POST_TAG = '__/aa-highlight__';
	var HITS_PER_PAGE = 5;

	function fetchMeilisearchResults(_a) {
		var searchClient = _a.searchClient, queries = _a.queries;
		return searchClient
			.search(queries.map(function (searchParameters) {
				var params = searchParameters.params, headers = __rest(searchParameters, ["params"]);
				return __assign(__assign({}, headers), { params: __assign({ hitsPerPage: HITS_PER_PAGE, highlightPreTag: HIGHLIGHT_PRE_TAG, highlightPostTag: HIGHLIGHT_POST_TAG }, params) });
			}))
			.then(function (response) {
				return response.results;
			});
	}

	function createRequester(fetcher, requesterId) {
		function execute(fetcherParams) {
			return fetcher({
				searchClient: fetcherParams.searchClient,
				queries: fetcherParams.requests.map(function (x) { return x.query; })
			}).then(function (responses) {
				return responses.map(function (response, index) {
					var _a = fetcherParams.requests[index], sourceId = _a.sourceId, transformResponse = _a.transformResponse;
					return {
						items: response,
						sourceId: sourceId,
						transformResponse: transformResponse
					};
				});
			});
		}
		return function createSpecifiedRequester(requesterParams) {
			return function requester(requestParams) {
				return __assign(__assign({ requesterId: requesterId, execute: execute }, requesterParams), requestParams);
			};
		};
	}

	var createMeilisearchRequester = createRequester(function (params) { return fetchMeilisearchResults(params); }, 'meilisearch');

	/**
	 * Retrieves Meilisearch results from multiple indexes.
	 */
	var getMeilisearchResults = createMeilisearchRequester({
		transformResponse: function (response) { return response.hits; }
	});

	exports.__moduleExports = instantMeilisearch_umd;
	exports.concatUserAgents = concatUserAgents;
	exports.createMeilisearchRequester = createMeilisearchRequester;
	exports.createRequester = createRequester;
	exports.createSearchClient = createSearchClient;
	exports.fetchMeilisearchResults = fetchMeilisearchResults;
	exports.getMeilisearchResults = getMeilisearchResults;
	exports.meilisearchAutocompleteClient = meilisearchAutocompleteClient;

	Object.defineProperty(exports, '__esModule', { value: true });

})));
