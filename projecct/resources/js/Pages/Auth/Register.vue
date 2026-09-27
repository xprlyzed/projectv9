<script>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { h } from 'vue';
export default {
    layout: (hh, page) => h(AuthLayout, { activeAuctions: page.props.activeAuctions || 0 }, () => page),
};
</script>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ activeAuctions: Number });

const form = useForm({
    role: 'buyer',
    name: '', username: '', email: '', phone: '',
    company_name: '', tax_number: '', iban: '', id_document: null,
    password: '', password_confirmation: '', terms: false,
});

const step = ref(1);
const termsError = ref(false);
const mismatchError = ref(false);
const idFileName = ref('');
const localErrors = reactive({});
const showTerms = ref(false);

const isSeller = computed(() => form.role === 'seller');
const totalSteps = computed(() => (isSeller.value ? 3 : 2));
const currentStepIndex = computed(() => {
    if (step.value === 1) return 1;
    if (step.value === 2) return 2;
    return isSeller.value ? 3 : 2;
});
const finalStepLabel = computed(() => isSeller.value ? 'Adım 3 / 3' : 'Adım 2 / 2');
const progressPct = computed(() => Math.round((currentStepIndex.value / totalSteps.value) * 100));

function clearErrs(fields) { fields.forEach(f => { delete localErrors[f]; }); }
function err(field) { return form.errors[field] || localErrors[field]; }

/* ---------- Telefon: sadece rakam, otomatik 0 önek, "0 505 021 8283" görünümü ---------- */
const phoneDisplay = ref('');
function normalizePhone(input) {
    let d = String(input || '').replace(/\D/g, '');
    if (d.length && d[0] !== '0') {
        if (d[0] === '5') d = '0' + d;      // baştaki 0 otomatik tamamlanır
    }
    d = d.slice(0, 11);                     // 05XXXXXXXXX → 11 hane
    const parts = [];
    parts.push(d.slice(0, 1));
    if (d.length > 1) parts.push(d.slice(1, 4));
    if (d.length > 4) parts.push(d.slice(4, 7));
    if (d.length > 7) parts.push(d.slice(7, 11));
    return { raw: d, display: parts.filter(Boolean).join(' ') };
}
function onPhoneInput(e) {
    const { raw, display } = normalizePhone(e.target.value);
    form.phone = raw;
    phoneDisplay.value = display;
}
function onPhoneBlur() {
    // Kullanıcı 5 ile başlayıp 0 yazmadıysa çıkışta düzelt + doğrula
    const { raw, display } = normalizePhone(form.phone);
    form.phone = raw;
    phoneDisplay.value = display;
    validateField('phone');
}

/* ---------- Alan bazlı (blur) doğrulama ---------- */
function validateField(field) {
    delete localErrors[field];
    if (field === 'name' && !form.name.trim()) localErrors.name = 'Ad Soyad zorunludur.';
    if (field === 'username') {
        const u = form.username.trim();
        if (!u) localErrors.username = 'Kullanıcı adı zorunludur.';
        else if (u.length < 3) localErrors.username = 'Kullanıcı adı en az 3 karakter olmalı.';
        else if (u.length > 30) localErrors.username = 'Kullanıcı adı en fazla 30 karakter olabilir.';
        else if (!/^[a-zA-Z0-9_.]+$/.test(u)) localErrors.username = 'Sadece harf, rakam, nokta ve alt çizgi kullanılabilir.';
    }
    if (field === 'email') {
        const em = form.email.trim();
        if (!em) localErrors.email = 'E-posta zorunludur.';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) localErrors.email = 'Geçerli bir e-posta adresi girin.';
    }
    if (field === 'phone') {
        if (!form.phone) localErrors.phone = 'GSM numarası zorunludur.';
        else if (!/^0[0-9]{10}$/.test(form.phone)) localErrors.phone = 'Geçerli bir GSM numarası girin (05XX XXX XXXX).';
    }
    if (field === 'tax_number') {
        if (!form.tax_number.trim()) localErrors.tax_number = 'Vergi numarası zorunludur.';
        else if (form.tax_number.trim().length > 20) localErrors.tax_number = 'Vergi numarası en fazla 20 karakter olabilir.';
    }
    if (field === 'iban') {
        const ib = form.iban.trim();
        if (!ib) localErrors.iban = 'IBAN zorunludur.';
        else if (ib.length < 26 || ib.length > 34) localErrors.iban = 'IBAN 26-34 karakter arası olmalıdır.';
    }
}

function validateStep1() {
    ['name', 'username', 'email', 'phone'].forEach(validateField);
    if (!form.role) localErrors.role = 'Hesap türü seçiniz.'; else delete localErrors.role;
    return !['name', 'username', 'email', 'phone', 'role'].some(f => localErrors[f]);
}
function validateStep2() {
    ['tax_number', 'iban'].forEach(validateField);
    if (!form.id_document) localErrors.id_document = 'Kimlik belgesi zorunludur.'; else delete localErrors.id_document;
    return !['tax_number', 'iban', 'id_document'].some(f => localErrors[f]);
}

function goStep1Next() {
    if (!validateStep1()) return;
    step.value = isSeller.value ? 2 : 3;
}
function goStep2Next() {
    if (!validateStep2()) return;
    step.value = 3;
}

const strength = computed(() => {
    const p = form.password || '';
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return score;
});
const strengthMeta = computed(() => {
    const map = [
        { w: '0%', c: 'transparent', t: '' },
        { w: '25%', c: '#ef4444', t: 'Zayıf' },
        { w: '50%', c: '#f59e0b', t: 'Orta' },
        { w: '75%', c: '#3b82f6', t: 'İyi' },
        { w: '100%', c: '#10b981', t: 'Güçlü' },
    ];
    return map[strength.value];
});

function onFile(e) {
    form.id_document = e.target.files[0] || null;
    idFileName.value = e.target.files[0]?.name || '';
    if (form.id_document) delete localErrors.id_document;
}

function submit() {
    mismatchError.value = form.password !== form.password_confirmation;
    termsError.value = !form.terms;
    if (mismatchError.value || termsError.value) return;
    form.post(route('register'), { forceFormData: true });
}

onMounted(() => {
    phoneDisplay.value = normalizePhone(form.phone).display;
    if (form.errors.tax_number || form.errors.iban || form.errors.company_name || form.errors.id_document) {
        step.value = 2;
    } else if (form.errors.password) {
        step.value = 3;
    }
});
</script>

<template>
    <Head title="Kaydol" />
    <form class="form w-100" @submit.prevent="submit" enctype="multipart/form-data">
        <div class="auth-header text-center mb-6">
            <img src="/assets/media/logos/logo-light.svg" class="logo-light auth-logo" alt="Artirdim">
            <img src="/assets/media/logos/logo-dark.svg" class="logo-dark auth-logo" alt="Artirdim">
        </div>

        <!-- Adım göstergesi (her adımda) -->
        <div class="reg-steps mb-6" data-testid="register-step-indicator">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted fs-8 fw-semibold">Adım {{ currentStepIndex }} / {{ totalSteps }}</span>
                <span class="fs-8 fw-semibold reg-step-name">
                    {{ step === 1 ? 'Hesap Bilgileri' : (step === 2 ? 'Satıcı Doğrulama' : 'Güvenlik') }}
                </span>
            </div>
            <div class="reg-progress"><div class="reg-progress-bar" :style="{ width: progressPct + '%' }"></div></div>
        </div>

        <div v-show="step === 1">
            <div class="mb-6">
                <label class="form-label text-muted fs-7 fw-semibold mb-3">Hesap Türü</label>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="role-card p-4 rounded-2 text-center" role="button" tabindex="0"
                             :class="{ selected: form.role === 'buyer' }"
                             @click="form.role = 'buyer'" @keyup.enter="form.role = 'buyer'"
                             data-testid="register-role-buyer">
                            <div class="symbol symbol-40px mx-auto mb-3">
                                <div class="symbol-label rounded-circle role-icon-wrap"><i class="bi bi-person-fill fs-4"></i></div>
                            </div>
                            <div class="fw-bold role-label">Alıcı</div>
                            <div class="text-muted fs-8 mt-1">Teklif ver, satın al</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="role-card p-4 rounded-2 text-center" role="button" tabindex="0"
                             :class="{ selected: form.role === 'seller' }"
                             @click="form.role = 'seller'" @keyup.enter="form.role = 'seller'"
                             data-testid="register-role-seller">
                            <div class="symbol symbol-40px mx-auto mb-3">
                                <div class="symbol-label rounded-circle role-icon-wrap"><i class="bi bi-shop fs-4"></i></div>
                            </div>
                            <div class="fw-bold role-label">Satıcı</div>
                            <div class="text-muted fs-8 mt-1">İlan ver, sat</div>
                        </div>
                    </div>
                </div>
                <div v-if="err('role')" class="text-danger small mt-2">{{ err('role') }}</div>
            </div>

            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="text" v-model="form.name" class="form-control" :class="{ 'is-invalid': err('name') }" placeholder="Ad Soyad" data-testid="register-name" @blur="validateField('name')" @keyup.enter="goStep1Next">
                    <label>Ad Soyad</label>
                </div>
                <div v-if="err('name')" class="text-danger small mt-1">{{ err('name') }}</div>
            </div>
            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="text" v-model="form.username" maxlength="30" class="form-control" :class="{ 'is-invalid': err('username') }" placeholder="kullanici_adi" autocomplete="username" data-testid="register-username" @blur="validateField('username')" @keyup.enter="goStep1Next">
                    <label>Kullanıcı Adı</label>
                </div>
                <div v-if="err('username')" class="text-danger small mt-1">{{ err('username') }}</div>
            </div>
            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="email" v-model="form.email" class="form-control" :class="{ 'is-invalid': err('email') }" placeholder="E-posta" data-testid="register-email" @blur="validateField('email')" @keyup.enter="goStep1Next">
                    <label>E-posta</label>
                </div>
                <div v-if="err('email')" class="text-danger small mt-1">{{ err('email') }}</div>
            </div>
            <div class="fv-row mb-6">
                <div class="form-floating">
                    <input type="tel" inputmode="numeric" :value="phoneDisplay" @input="onPhoneInput" @blur="onPhoneBlur" @keyup.enter="goStep1Next" class="form-control" :class="{ 'is-invalid': err('phone') }" placeholder="0 5XX XXX XXXX" maxlength="14" data-testid="register-phone">
                    <label>GSM Numarası</label>
                </div>
                <div v-if="err('phone')" class="text-danger small mt-1">{{ err('phone') }}</div>
            </div>

            <button type="button" class="btn btn-auth-primary btn-lg w-100" @click="goStep1Next" data-testid="register-step1-next">Devam et</button>
            <div class="text-center mt-4">
                <Link :href="route('login')" class="btn btn-auth-outline btn-lg w-100">Zaten hesabın var mı? Giriş yap</Link>
            </div>
        </div>

        <div v-show="step === 2">
            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="text" v-model="form.company_name" class="form-control" :class="{ 'is-invalid': form.errors.company_name }" placeholder="Şirket Adı">
                    <label>Şirket Adı <span class="text-muted fs-8">(opsiyonel)</span></label>
                </div>
                <div v-if="form.errors.company_name" class="text-danger small mt-1">{{ form.errors.company_name }}</div>
            </div>
            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="text" v-model="form.tax_number" class="form-control" :class="{ 'is-invalid': err('tax_number') }" placeholder="Vergi Numarası" @blur="validateField('tax_number')">
                    <label>Vergi Numarası</label>
                </div>
                <div v-if="err('tax_number')" class="text-danger small mt-1">{{ err('tax_number') }}</div>
            </div>
            <div class="fv-row mb-4">
                <div class="form-floating">
                    <input type="text" v-model="form.iban" maxlength="34" class="form-control il-186be751" :class="{ 'is-invalid': err('iban') }" placeholder="IBAN" @blur="validateField('iban')">
                    <label>IBAN</label>
                </div>
                <div v-if="err('iban')" class="text-danger small mt-1">{{ err('iban') }}</div>
            </div>
            <div class="fv-row mb-6">
                <label class="form-label text-muted fs-7 fw-semibold mb-2">Kimlik Belgesi <span class="text-muted fs-8">(JPG, PNG veya PDF — maks. 5MB)</span></label>
                <input type="file" class="form-control" :class="{ 'is-invalid': err('id_document') }" accept=".jpg,.jpeg,.png,.pdf" @change="onFile" data-testid="register-id-document">
                <div v-if="idFileName" class="text-muted small mt-1">{{ idFileName }}</div>
                <div v-if="err('id_document')" class="text-danger small mt-1">{{ err('id_document') }}</div>
            </div>

            <div class="d-flex gap-3">
                <button type="button" class="btn btn-auth-outline btn-lg py-3 fw-semibold il-59c8d6ff" @click="step = 1">Geri</button>
                <button type="button" class="btn btn-auth-primary btn-lg py-3 fw-semibold flex-grow-1" @click="goStep2Next" data-testid="register-step2-next">Devam et</button>
            </div>
        </div>

        <div v-show="step === 3">
            <div class="fv-row mb-4">
                <div class="form-floating position-relative" data-kt-password-meter="true">
                    <input type="password" v-model="form.password" class="form-control auth-input pe-5" :class="{ 'is-invalid': form.errors.password }" placeholder="Şifre" autocomplete="off" data-testid="register-password">
                    <label>Şifre</label>
                    <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0" data-kt-password-meter-control="visibility">
                        <i class="bi bi-eye-slash fs-2"></i><i class="bi bi-eye fs-2 d-none"></i>
                    </span>
                </div>
                <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
            </div>

            <div class="mb-4">
                <div class="il-2cd72ac1">
                    <div :style="{ height: '100%', width: strengthMeta.w, background: strengthMeta.c, borderRadius: '2px', transition: 'width .3s, background .3s' }"></div>
                </div>
                <span :style="{ fontSize: '11px', fontWeight: 600, color: strengthMeta.c }">{{ strengthMeta.t }}</span>
            </div>

            <div class="fv-row mb-6">
                <div class="form-floating position-relative" data-kt-password-meter="true">
                    <input type="password" v-model="form.password_confirmation" class="form-control auth-input pe-5" placeholder="Şifre Tekrar" autocomplete="off" data-testid="register-password-confirm" @keyup.enter="submit">
                    <label>Şifre Tekrar</label>
                    <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0" data-kt-password-meter-control="visibility">
                        <i class="bi bi-eye-slash fs-2"></i><i class="bi bi-eye fs-2 d-none"></i>
                    </span>
                </div>
                <div v-if="mismatchError" class="text-danger small mt-1">Şifreler eşleşmiyor.</div>
            </div>

            <div class="mb-8">
                <label class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" v-model="form.terms" data-testid="register-terms">
                    <span class="form-check-label text-muted fs-7">
                        <a href="#" class="text-primary" @click.prevent="showTerms = true" data-testid="register-terms-link">Kullanım koşullarını</a> okudum ve kabul ediyorum
                    </span>
                </label>
                <div v-if="termsError" class="text-danger small mt-1">Kullanım koşullarını kabul etmelisiniz.</div>
            </div>

            <div class="d-flex gap-3">
                <button type="button" class="btn btn-auth-outline btn-lg py-3 fw-semibold il-59c8d6ff" @click="step = isSeller ? 2 : 1">Geri</button>
                <button type="submit" class="btn btn-auth-primary btn-lg py-3 fw-semibold flex-grow-1" :disabled="form.processing" data-testid="register-submit">
                    <span v-if="!form.processing">Kayıt Ol</span>
                    <span v-else>Lütfen bekleyin... <span class="spinner-border spinner-border-sm ms-2 align-middle"></span></span>
                </button>
            </div>
        </div>
    </form>

    <!-- Kullanım Koşulları Modalı -->
    <Teleport to="body">
        <div v-if="showTerms" class="reg-modal-overlay" @click.self="showTerms = false" @keyup.esc="showTerms = false" tabindex="0" data-testid="register-terms-modal">
            <div class="reg-modal" role="dialog" aria-modal="true" aria-labelledby="reg-terms-title">
                <div class="reg-modal-head">
                    <h3 id="reg-terms-title" class="reg-modal-title"><i class="bi bi-shield-check me-2"></i>Kullanım Koşulları & Gizlilik</h3>
                    <button type="button" class="reg-modal-close" @click="showTerms = false" data-testid="register-terms-close" aria-label="Kapat"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="reg-modal-body">
                    <p>artirdim.com'a üye olarak aşağıdaki koşulları kabul etmiş olursunuz:</p>
                    <ul>
                        <li>Verdiğiniz teklifler bağlayıcıdır; kazandığınız müzayedelerde ödeme yükümlülüğünüz doğar.</li>
                        <li>Hesap bilgilerinizin doğruluğundan ve güvenliğinden siz sorumlusunuz.</li>
                        <li>Satıcı hesapları için kimlik ve vergi bilgileri doğrulamaya tabidir.</li>
                        <li>Platform, kurallara aykırı davranışlarda hesabı askıya alma hakkını saklı tutar.</li>
                    </ul>
                    <p class="mb-1"><strong>Kişisel Verilerin Korunması (KVKK):</strong></p>
                    <p>Kayıt sırasında paylaştığınız kişisel veriler yalnızca hizmetin sunulması, güvenlik ve
                        yasal yükümlülükler kapsamında işlenir; üçüncü taraflarla izinsiz paylaşılmaz.</p>
                    <p class="reg-modal-note">Detaylı bilgi için <Link :href="route('privacy')" class="text-primary">Gizlilik Politikası</Link> sayfamızı inceleyebilirsiniz.</p>
                </div>
                <div class="reg-modal-foot">
                    <button type="button" class="btn btn-auth-outline" @click="showTerms = false">Kapat</button>
                    <button type="button" class="btn btn-auth-primary" @click="form.terms = true; showTerms = false; termsError = false" data-testid="register-terms-accept">Okudum, Kabul Ediyorum</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped src="../../../css/pages/auth-register.css"></style>

<style scoped>
.reg-progress { height: 6px; background: rgba(148,163,184,.25); border-radius: 6px; overflow: hidden; }
.reg-progress-bar { height: 100%; background: linear-gradient(90deg, var(--color-primary, #3b82f6), var(--color-primary-hover, #2563eb)); border-radius: 6px; transition: width .35s ease; }
.reg-step-name { color: var(--color-primary, #3b82f6); }

.role-card { transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease; cursor: pointer; }
.role-card:hover { transform: translateY(-2px); }
.role-card:focus-visible { outline: 2px solid var(--color-primary, #3b82f6); outline-offset: 2px; }

.reg-modal-overlay {
    position: fixed; inset: 0; z-index: 20000;
    background: rgba(3, 6, 20, .62); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; padding: 18px;
    animation: reg-fade .2s ease;
}
.reg-modal {
    width: 100%; max-width: 520px; max-height: 88vh; overflow: hidden;
    background: var(--bg-soft, #12141f); color: var(--text, #fff);
    border: 1px solid var(--border, rgba(255,255,255,.1)); border-radius: 18px;
    box-shadow: 0 24px 70px rgba(0,0,0,.5); display: flex; flex-direction: column;
    animation: reg-pop .22s ease;
}
.reg-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--border, rgba(255,255,255,.08)); }
.reg-modal-title { font-size: 16px; font-weight: 700; margin: 0; }
.reg-modal-close { background: transparent; border: none; color: var(--muted, #9aa4b2); font-size: 16px; cursor: pointer; width: 34px; height: 34px; border-radius: 9px; transition: background .15s; }
.reg-modal-close:hover { background: rgba(148,163,184,.15); color: var(--text, #fff); }
.reg-modal-body { padding: 20px; overflow-y: auto; font-size: 14px; line-height: 1.7; color: var(--muted, #cbd3e1); }
.reg-modal-body ul { padding-left: 18px; margin: 10px 0 16px; }
.reg-modal-body li { margin-bottom: 7px; }
.reg-modal-note { margin-top: 14px; font-size: 13px; }
.reg-modal-foot { display: flex; gap: 10px; justify-content: flex-end; padding: 16px 20px; border-top: 1px solid var(--border, rgba(255,255,255,.08)); }
.reg-modal-foot .btn { min-width: 120px; }

@keyframes reg-fade { from { opacity: 0; } to { opacity: 1; } }
@keyframes reg-pop { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

@media (max-width: 576px) {
    .reg-modal-foot { flex-direction: column-reverse; }
    .reg-modal-foot .btn { width: 100%; }
}
</style>
