<!-- src/views/CustomerVerification/CustomerUpdateForm.vue -->
<template>
  <div class="min-h-screen bg-slate-50">
    <!-- BRAND BAR (sticky) -->
    <div class="sticky top-0 z-50 bg-theme-1 text-white shadow-md">
      <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between gap-3">
        <img :src="logoUrl" alt="TDS" class="h-20 w-auto" />
        <span class="font-header text-2xl text-white">Customer Onboarding Portal</span>
        <img :src="logoUrl2" alt="CRS" class="h-24 w-auto" />
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-5 py-6">
      <!-- Token sudah kedaluwarsa -->
      <Alert v-if="tokenStatus === 'expired'" variant="soft-danger" class="mb-6">
        Link verifikasi ini sudah kedaluwarsa. Silakan hubungi tim marketing TDS untuk mendapatkan link baru.
      </Alert>

      <!-- Token sudah pernah dipakai submit -->
      <Alert v-else-if="tokenStatus === 'used'" variant="soft-success" class="mb-6">
        Terima kasih, data verifikasi untuk perusahaan Anda sudah pernah dikirim sebelumnya. Tidak ada tindakan lebih
        lanjut yang diperlukan.
      </Alert>

      <template v-else>
        <!-- Progress indicator (display-only) -->
        <div class="mb-6 overflow-x-auto rounded-lg bg-white p-4 shadow-sm">
          <div class="min-w-[720px]">
            <Stepper :steps="stepperItems" direction="horizontal" size="sm" show-label :show-status-badge="false" />
          </div>
        </div>

        <!-- =========================
             STEP 1 – CORPORATE DETAILS
             ========================= -->
        <div v-show="step === 1" class="space-y-6 mb-8">
          <CardSection title="Corporate Details" icon="Building2">
            <div class="space-y-5">
              <div>
                <FormLabel class="font-label !mb-1 block">FULL REGISTERED COMPANY NAME
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.corporate.nama" type="text" placeholder="Company name"
                  :class="inputClass('corporate.nama')" />
                <small v-if="fieldError('corporate.nama')" class="block input-error-text">{{
                  fieldError('corporate.nama') }}</small>
              </div>

              <div>
                <FormLabel class="font-label !mb-1 block">HOLDING (if any)</FormLabel>
                <FormInput v-model="form.corporate.holding" type="text" />
              </div>

              <div>
                <FormLabel class="font-label !mb-1 block">Print Product</FormLabel>
                <FormInput v-model="form.corporate.print_product" type="text" />
              </div>
            </div>
          </CardSection>

          <!-- Address of Head Office -->
          <div class="rounded-lg bg-white shadow-sm p-6">
            <div class="font-section mb-3 pb-2 border-b border-slate-100">HEAD OFFICE ADDRESS</div>
            <div class="space-y-5">
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Email
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormInput v-model="form.corporate.email" type="email" :class="inputClass('corporate.email')" />
                  <small v-if="fieldError('corporate.email')" class="block input-error-text">{{
                    fieldError('corporate.email') }}</small>
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Website</FormLabel>
                  <FormInput v-model="form.corporate.website" type="text" />
                </div>

                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Address
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormTextarea v-model="form.corporate.alamat" rows="3" :class="inputClass('corporate.alamat')" />
                  <small v-if="fieldError('corporate.alamat')" class="block input-error-text">{{
                    fieldError('corporate.alamat') }}</small>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Provinsi -->
                <div>
                  <FormLabel class="font-label !mb-1 block">Province / Provinsi
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormSelect :value="form.corporate.province_id ?? ''" @change="onCorporateProvinceChange"
                    :class="inputClass('corporate.province_id')">
                    <option value="" disabled>Select province</option>
                    <option v-for="p in corporateRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}
                    </option>
                  </FormSelect>
                  <small v-if="fieldError('corporate.province_id')" class="block input-error-text">{{
                    fieldError('corporate.province_id') }}</small>
                </div>

                <!-- Kabupaten -->
                <div>
                  <FormLabel class="font-label !mb-1 block">City / Kota (Kabupaten)
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormSelect :value="form.corporate.regency_id ?? ''" @change="onCorporateRegencyChange"
                    :disabled="!form.corporate.province_id" :class="inputClass('corporate.regency_id')">
                    <option value="" disabled>
                      {{ form.corporate.province_id ? 'Select city/regency' : '-- Select province first --' }}
                    </option>
                    <option v-for="k in corporateRegion.regencies.value" :key="k.id" :value="k.id">{{ k.name }}
                    </option>
                  </FormSelect>
                  <small v-if="fieldError('corporate.regency_id')" class="block input-error-text">{{
                    fieldError('corporate.regency_id') }}</small>
                </div>

                <!-- Kecamatan (dropdown, mengganti free-text lama) -->
                <div>
                  <FormLabel class="font-label !mb-1 block">Districts / Kecamatan</FormLabel>
                  <FormSelect :value="form.corporate.district_id ?? ''" @change="onCorporateDistrictChange"
                    :disabled="!form.corporate.regency_id">
                    <option value="">
                      {{ form.corporate.regency_id ? 'Select district' : '-- Select city/regency first --' }}
                    </option>
                    <option v-for="d in corporateRegion.districts.value" :key="d.id" :value="d.id">{{ d.name }}
                    </option>
                  </FormSelect>
                </div>

                <!-- Kelurahan (dropdown, mengganti free-text lama) -->
                <div>
                  <FormLabel class="font-label !mb-1 block">Sub-Districts / Kelurahan</FormLabel>
                  <FormSelect :value="form.corporate.village_id ?? ''" @change="onCorporateVillageChange"
                    :disabled="!form.corporate.district_id">
                    <option value="">
                      {{ form.corporate.district_id ? 'Select sub-district' : '-- Select district first --' }}
                    </option>
                    <option v-for="v in corporateRegion.villages.value" :key="v.id" :value="v.id">{{ v.name }}
                    </option>
                  </FormSelect>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Postal Code</FormLabel>
                  <FormInput v-model="form.corporate.postal_code" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Telephone
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormInput v-model="form.corporate.telepon" type="text" :class="inputClass('corporate.telepon')" />
                  <small v-if="fieldError('corporate.telepon')" class="block input-error-text">{{
                    fieldError('corporate.telepon') }}</small>
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Fax</FormLabel>
                  <FormInput v-model="form.corporate.fax" type="text" />
                </div>
              </div>
            </div>
          </div>

          <!-- Registered Street Address -->
          <div class="rounded-lg bg-white shadow-sm p-6">
            <div class="font-section mb-3 pb-2 border-b border-slate-100">NPWP ADDRESS (REGISTERED)</div>
            <div class="space-y-5">
              <FormCheck>
                <FormCheck.Input id="same-as-head-office" type="checkbox" v-model="sameAsHeadOffice" />
                <FormCheck.Label htmlFor="same-as-head-office">Same as Head Office Address</FormCheck.Label>
              </FormCheck>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Email
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormInput v-model="form.registered.email" type="email" :class="inputClass('registered.email')" />
                  <small v-if="fieldError('registered.email')" class="block input-error-text">{{
                    fieldError('registered.email') }}</small>
                </div>

                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Address
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormTextarea v-model="form.registered.alamat" rows="3" :class="inputClass('registered.alamat')" />
                  <small v-if="fieldError('registered.alamat')" class="block input-error-text">{{
                    fieldError('registered.alamat') }}</small>
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">Province / Provinsi
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormSelect :value="form.registered.province_id ?? ''" @change="onRegisteredProvinceChange"
                    :class="inputClass('registered.province_id')">
                    <option value="" disabled>Select province</option>
                    <option v-for="p in registeredRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}
                    </option>
                  </FormSelect>
                  <small v-if="fieldError('registered.province_id')" class="block input-error-text">{{
                    fieldError('registered.province_id') }}</small>
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">City / Kabupaten
                    <RequiredAsterisk />
                  </FormLabel>
                  <FormSelect :value="form.registered.regency_id ?? ''" @change="onRegisteredRegencyChange"
                    :disabled="!form.registered.province_id" :class="inputClass('registered.regency_id')">
                    <option value="" disabled>
                      {{ form.registered.province_id ? 'Select city/regency' : '-- Select province first --' }}
                    </option>
                    <option v-for="k in registeredRegion.regencies.value" :key="k.id" :value="k.id">{{ k.name }}
                    </option>
                  </FormSelect>
                  <small v-if="fieldError('registered.regency_id')" class="block input-error-text">{{
                    fieldError('registered.regency_id') }}</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Product Delivery Address -->
          <div class="rounded-lg bg-white shadow-sm p-6">
            <div class="font-section mb-3 pb-2 border-b border-slate-100">PRODUCT DELIVERY ADDRESS</div>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <FormLabel class="font-label !mb-1 block">Address 1</FormLabel>
                <FormTextarea v-model="form.delivery.alamat1" rows="3" />
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Address 2</FormLabel>
                <FormTextarea v-model="form.delivery.alamat2" rows="3" />
              </div>
              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">Address 3</FormLabel>
                <FormTextarea v-model="form.delivery.alamat3" rows="3" />
              </div>
            </div>
          </div>

          <!-- Type of Business + Ownership -->
          <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">TYPE OF BUSINESS</div>
              <div class="space-y-2">
                <FormCheck v-for="(opt, idx) in typeBusiness" :key="opt">
                  <FormCheck.Input :id="'tipe-bisnis-' + idx" type="radio" :value="opt"
                    v-model="form.corporate.tipe_bisnis" />
                  <FormCheck.Label :htmlFor="'tipe-bisnis-' + idx">{{ opt }}</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="tipe-bisnis-other" type="radio" value="Other"
                    v-model="form.corporate.tipe_bisnis" />
                  <FormCheck.Label htmlFor="tipe-bisnis-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.corporate.tipe_bisnis === 'Other'" v-model="form.corporate.tipe_bisnis_lain"
                  type="text" placeholder="Specify" />
              </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">OWNERSHIP</div>
              <div class="space-y-2">
                <FormCheck v-for="(opt, idx) in ownershipOpts" :key="opt">
                  <FormCheck.Input :id="'ownership-' + idx" type="radio" :value="opt"
                    v-model="form.corporate.ownership" />
                  <FormCheck.Label :htmlFor="'ownership-' + idx">{{ opt }}</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="ownership-other" type="radio" value="Other" v-model="form.corporate.ownership" />
                  <FormCheck.Label htmlFor="ownership-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.corporate.ownership === 'Other'" v-model="form.corporate.ownership_lain"
                  type="text" placeholder="Specify" />
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
             STEP 2 – DOCUMENTATION
             ========================= -->
        <CardSection v-show="step === 2" title="Documentation" icon="FileText" class="mb-8">
          <div class="space-y-5">
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Akta -->
              <div>
                <FormLabel class="font-label !mb-1 block">Certificate Number (Akta Pendirian)</FormLabel>
                <FormInput v-model="form.docs.certificate_number" type="text" class="mb-2" />
                <FileUploadField v-model="aktaFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
              </div>

              <!-- NPWP -->
              <div>
                <FormLabel class="font-label !mb-1 block">NPWP Number
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.docs.npwp_number" type="text" class="mb-2"
                  :class="inputClass('docs.npwp_number')" />
                <small v-if="fieldError('docs.npwp_number')" class="block input-error-text">{{
                  fieldError('docs.npwp_number') }}</small>
                <FileUploadField v-model="npwpFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
              </div>

              <!-- NIB -->
              <div>
                <FormLabel class="font-label !mb-1 block">NIB Number
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.docs.nib_number" type="text" class="mb-2"
                  :class="inputClass('docs.nib_number')" />
                <small v-if="fieldError('docs.nib_number')" class="block input-error-text">{{
                  fieldError('docs.nib_number') }}</small>
                <FileUploadField v-model="nibFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
              </div>

              <!-- Dokumen lain (multiple: surat keterangan domisili, fixed asset, dll) -->
              <div>
                <FormLabel class="font-label !mb-1 block">Other Documents (Domicile Certificate, Fixed Asset, etc.)
                </FormLabel>
                <FormInput v-model="form.docs.other_doc" type="text" placeholder="Document description" class="mb-2" />

                <ul v-if="form.docs.other_files.length"
                  class="mt-2 mb-2 space-y-1 text-sm text-slate-600 list-disc list-inside">
                  <li v-for="(f, idx) in form.docs.other_files" :key="idx">{{ f.name }}</li>
                </ul>

                <FileUploadField v-model="otherFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10"
                  :multiple="true" />
              </div>
            </div>

            <p class="font-caption">
              * Max size sesuai kebijakan (mis. 2–10MB). Ekstensi yang diizinkan: jpg, jpeg, png, pdf, zip, rar.
            </p>
          </div>
        </CardSection>

        <!-- =========================
             STEP 3 – PAYMENT TERM & BANKING DETAIL
             ========================= -->
        <div v-show="step === 3" class="space-y-6 mb-8">
          <CardSection title="Payment Info" icon="Wallet">
            <div class="space-y-6">
              <!-- Payment Metode -->
              <div>
                <div class="mb-2 flex items-center gap-1 font-section">
                  PAYMENT METHOD
                  <RequiredAsterisk />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                  <div v-for="m in paymentMethodChoices" :key="m"
                    class="relative flex flex-col items-center gap-2 rounded-xl border p-3 text-center cursor-pointer transition-colors"
                    :class="form.payment.payment_method === m
                      ? 'border-2 border-primary bg-primary/5'
                      : 'border-gray-200 hover:border-primary/30 hover:bg-primary/5'"
                    @click="form.payment.payment_method = m">
                    <div v-if="form.payment.payment_method === m"
                      class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-white">
                      <Lucide icon="Check" class="h-3 w-3" />
                    </div>
                    <Lucide :icon="paymentMethodIcons[m]" class="h-5 w-5 text-primary" />
                    <span class="font-body text-xs">{{ m }}</span>
                  </div>
                </div>
                <FormInput v-if="form.payment.payment_method === 'Other'" v-model="form.payment.payment_method_other"
                  type="text" class="mt-3" placeholder="Specify" />
                <small v-if="fieldError('payment.payment_method')" class="block input-error-text">{{
                  fieldError('payment.payment_method') }}</small>
              </div>

              <!-- Payment Term -->
              <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
                <div>
                  <FormLabel class="font-label !mb-1 block">Payment Type</FormLabel>
                  <FormSelect v-model="form.payment.payment_type">
                    <option value="">Select Payment Type</option>
                    <option value="CASH">CASH</option>
                    <option value="CREDIT">CREDIT</option>
                  </FormSelect>
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Currency</FormLabel>
                  <FormInput v-model="form.payment.currency" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Bank Name</FormLabel>
                  <FormInput v-model="form.payment.bank_name" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Account Number</FormLabel>
                  <FormInput v-model="form.payment.account_number" type="text" />
                </div>
                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Bank Address</FormLabel>
                  <FormTextarea v-model="form.payment.bank_address" rows="3" />
                </div>
              </div>
            </div>
          </CardSection>

          <!-- Fasilitas Kredit & Perpajakan -->
          <div class="rounded-lg bg-white shadow-sm p-6">
            <div class="font-section mb-3 pb-2 border-b border-slate-100">CREDIT FACILITY &amp; TAX</div>
            <div class="space-y-5">
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <div class="font-section mb-2">Have Credit Facility or Bank Loan?</div>
                  <div class="grid grid-cols-2 gap-3 max-w-xs">
                    <div class="rounded-lg border py-2 text-center cursor-pointer transition-colors" :class="form.payment.has_credit === true
                      ? 'border-2 border-primary bg-primary/5 font-semibold text-primary'
                      : 'border-gray-200 hover:border-primary/30'" @click="form.payment.has_credit = true">
                      Yes
                    </div>
                    <div class="rounded-lg border py-2 text-center cursor-pointer transition-colors" :class="form.payment.has_credit === false
                      ? 'border-2 border-primary bg-primary/5 font-semibold text-primary'
                      : 'border-gray-200 hover:border-primary/30'" @click="form.payment.has_credit = false">
                      No
                    </div>
                  </div>
                  <FormInput v-if="form.payment.has_credit === true" v-model="form.payment.creditor_name" type="text"
                    class="mt-3" placeholder="Credit or Loan Provider Name" />
                </div>

                <div>
                  <div class="mb-2 flex items-center gap-1 font-section">
                    SUBMIT AND PAYMENT SCHEDULE
                    <RequiredAsterisk />
                  </div>
                  <div class="grid grid-cols-2 gap-3 max-w-xs">
                    <div class="rounded-lg border py-2 text-center cursor-pointer transition-colors" :class="form.payment.schedule === 'Every Day'
                      ? 'border-2 border-primary bg-primary/5 font-semibold text-primary'
                      : 'border-gray-200 hover:border-primary/30'" @click="form.payment.schedule = 'Every Day'">
                      Every Day
                    </div>
                    <div class="rounded-lg border py-2 text-center cursor-pointer transition-colors" :class="form.payment.schedule === 'Other'
                      ? 'border-2 border-primary bg-primary/5 font-semibold text-primary'
                      : 'border-gray-200 hover:border-primary/30'" @click="form.payment.schedule = 'Other'">
                      Other
                    </div>
                  </div>
                  <FormInput v-if="form.payment.schedule === 'Other'" v-model="form.payment.schedule_other" type="text"
                    class="mt-3" placeholder="Specify" />
                  <small v-if="fieldError('payment.schedule')" class="block input-error-text w-full">{{
                    fieldError('payment.schedule') }}</small>
                </div>
              </div>

              <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
                <div>
                  <div class="font-section mb-2">INVOICES</div>
                  <FormCheck>
                    <FormCheck.Input id="invoice-tax" type="checkbox" v-model="form.payment.invoice_tax" />
                    <FormCheck.Label htmlFor="invoice-tax">Tax Invoice (Faktur Pajak)</FormCheck.Label>
                  </FormCheck>
                </div>

                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">REMARKS (If Any)</FormLabel>
                  <FormTextarea v-model="form.payment.note" rows="3" />
                </div>
              </div>
            </div>
          </div>

          <!-- PIC Invoice -->
          <div class="rounded-lg bg-white shadow-sm p-6">
            <div class="mb-1 flex items-center gap-1 font-section pb-2 border-b border-slate-100">
              PIC INVOICE
              <RequiredAsterisk />
            </div>
            <p class="font-caption mb-3 mt-3">
              Nama, Telepon, dan Mobile PIC Invoice wajib diisi sebelum submit.
            </p>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <FormLabel class="font-label !mb-1 block">PIC Name
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.invoice.pic.name" type="text" :class="inputClass('invoice.pic.name')" />
                <small v-if="fieldError('invoice.pic.name')" class="block input-error-text">{{
                  fieldError('invoice.pic.name') }}</small>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Position</FormLabel>
                <FormInput v-model="form.invoice.pic.position" type="text" />
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Phone
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.invoice.pic.telephone" type="text"
                  :class="inputClass('invoice.pic.telephone')" />
                <small v-if="fieldError('invoice.pic.telephone')" class="block input-error-text">{{
                  fieldError('invoice.pic.telephone') }}</small>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Mobile / Cell Phone
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.invoice.pic.mobile" type="text" :class="inputClass('invoice.pic.mobile')" />
                <small v-if="fieldError('invoice.pic.mobile')" class="block input-error-text">{{
                  fieldError('invoice.pic.mobile') }}</small>
              </div>
              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">Email</FormLabel>
                <FormInput v-model="form.invoice.pic.email" type="email" />
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
             STEP 4 – SUPPLY SCHEME
             ========================= -->
        <CardSection v-show="step === 4" title="Supply Scheme" icon="Truck" class="mb-8">
          <div class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <FormLabel class="font-label !mb-1 block">SUPPLY SCHEME DETAILS
                  <RequiredAsterisk />
                </FormLabel>
                <FormSelect v-model="form.supply.scheme_details" :class="inputClass('supply.scheme_details')">
                  <option value="">Select one</option>
                  <option v-for="opt in supplySchemeOptions" :key="opt" :value="opt">{{ opt }}</option>
                </FormSelect>
                <small v-if="fieldError('supply.scheme_details')" class="block input-error-text">{{
                  fieldError('supply.scheme_details') }}</small>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">VOLUME PER MONTH
                  <RequiredAsterisk />
                </FormLabel>
                <FormInput v-model="form.supply.volume_per_month" type="number" min="0"
                  :class="inputClass('supply.volume_per_month')" />
                <small v-if="fieldError('supply.volume_per_month')" class="block input-error-text">{{
                  fieldError('supply.volume_per_month') }}</small>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <FormLabel class="font-label !mb-1 block">OPERATIONAL HOUR – FROM</FormLabel>
                  <FormInput v-model="form.supply.operational_from" type="time" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">TO</FormLabel>
                  <FormInput v-model="form.supply.operational_to" type="time" />
                </div>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">INCO TERMS
                  <RequiredAsterisk />
                </FormLabel>
                <div class="space-y-2 rounded-md border border-transparent p-2" :class="inputClass('supply.inco_terms')">
                  <FormCheck v-for="(opt, idx) in incoTermsOptions" :key="opt.code">
                    <FormCheck.Input :id="'inco-terms-' + idx" type="radio" :value="opt.code"
                      v-model="form.supply.inco_terms" />
                    <FormCheck.Label :htmlFor="'inco-terms-' + idx">{{ opt.code }} - {{ opt.label }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="inco-terms-other" type="radio" value="Other"
                      v-model="form.supply.inco_terms" />
                    <FormCheck.Label htmlFor="inco-terms-other">Other,</FormCheck.Label>
                  </FormCheck>
                  <FormInput v-if="form.supply.inco_terms === 'Other'" v-model="form.supply.inco_terms_other"
                    type="text" placeholder="Specify" />
                </div>
                <small v-if="fieldError('supply.inco_terms')" class="block input-error-text">{{
                  fieldError('supply.inco_terms') }}</small>
              </div>
            </div>
          </div>
        </CardSection>

        <!-- =========================
             STEP 5 – LOGISTIC
             ========================= -->
        <div v-show="step === 5" class="space-y-6 mb-8">
          <CardSection title="Logistic Info" icon="PackageCheck">
            <div>
              <div class="font-section mb-2">DESCRIPTION AND CAPACITY OF FACILITIES BUSINESS LOCATION</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <FormLabel class="font-label !mb-1 block">Area (Location Size)</FormLabel>
                  <FormTextarea v-model="form.logistic.area" rows="3" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Security Environment / Business Area</FormLabel>
                  <FormTextarea v-model="form.logistic.security_env" rows="3" />
                </div>
              </div>
            </div>
          </CardSection>

          <!-- Conditions Around Locations + Storage Facility -->
          <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">CONDITIONS AROUND LOCATIONS</div>
              <div class="space-y-2">
                <FormCheck>
                  <FormCheck.Input id="conditions-industri" type="radio" value="Industri" v-model="form.logistic.env" />
                  <FormCheck.Label htmlFor="conditions-industri">Industrial</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="conditions-pemukiman" type="radio" value="Pemukiman"
                    v-model="form.logistic.env" />
                  <FormCheck.Label htmlFor="conditions-pemukiman">Residential</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="conditions-other" type="radio" value="Other" v-model="form.logistic.env" />
                  <FormCheck.Label htmlFor="conditions-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.env === 'Other'" v-model="form.logistic.env_other" type="text"
                  placeholder="Specify" />
              </div>

              <div class="mt-4">
                <FormLabel class="font-label !mb-1 block">Description Of Condition</FormLabel>
                <FormTextarea v-model="form.logistic.condition_desc" rows="3" />
              </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">STORAGE FACILITY</div>
              <div class="space-y-2">
                <FormCheck>
                  <FormCheck.Input id="storage-indoor" type="radio" value="Indoor" v-model="form.logistic.storage" />
                  <FormCheck.Label htmlFor="storage-indoor">Indoor</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="storage-outdoor" type="radio" value="Outdoor" v-model="form.logistic.storage" />
                  <FormCheck.Label htmlFor="storage-outdoor">Outdoor</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="storage-other" type="radio" value="Other" v-model="form.logistic.storage" />
                  <FormCheck.Label htmlFor="storage-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.storage === 'Other'" v-model="form.logistic.storage_other" type="text"
                  placeholder="Specify" />
              </div>

              <div class="mt-4">
                <FormLabel class="font-label !mb-1 block">Description Of Storage Facility</FormLabel>
                <FormTextarea v-model="form.logistic.storage_desc" rows="3" />
              </div>
            </div>
          </div>

          <!-- Operating Hours + Quality Checking -->
          <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">LOGISTICS DETAIL – OPERATING HOURS</div>
              <div class="space-y-2">
                <FormCheck>
                  <FormCheck.Input id="operating-hours-0800" type="radio" value="08.00 - 17.00"
                    v-model="form.logistic.operating_hours" />
                  <FormCheck.Label htmlFor="operating-hours-0800">08.00 - 17.00</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="operating-hours-24" type="radio" value="24 Hours"
                    v-model="form.logistic.operating_hours" />
                  <FormCheck.Label htmlFor="operating-hours-24">24 Hours</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="operating-hours-other" type="radio" value="Other"
                    v-model="form.logistic.operating_hours" />
                  <FormCheck.Label htmlFor="operating-hours-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.operating_hours === 'Other'"
                  v-model="form.logistic.operating_hours_other" type="text" placeholder="Specify" />
              </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">QUALITY CHECKING</div>
              <div class="space-y-2">
                <FormCheck>
                  <FormCheck.Input id="quality-density" type="checkbox" v-model="form.logistic.quality_density" />
                  <FormCheck.Label htmlFor="quality-density">Lab Test</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="quality-other" type="checkbox" v-model="form.logistic.quality_other_enabled" />
                  <FormCheck.Label htmlFor="quality-other">Others,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.quality_other_enabled === true" v-model="form.logistic.quality_other"
                  type="text" placeholder="Specify" />
              </div>
            </div>
          </div>

          <!-- Quantity Checking + Capacity Info -->
          <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-lg bg-white shadow-sm p-6">
              <div class="font-section mb-3 pb-2 border-b border-slate-100">QUANTITY CHECKING</div>
              <div class="space-y-2">
                <FormCheck v-for="(opt, idx) in quantityCheckingOptions" :key="opt">
                  <FormCheck.Input :id="'quantity-checking-' + idx" type="radio" :value="opt"
                    v-model="form.logistic.volume_measurement" />
                  <FormCheck.Label :htmlFor="'quantity-checking-' + idx">{{ opt }}</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="quantity-checking-other" type="radio" value="Other"
                    v-model="form.logistic.volume_measurement" />
                  <FormCheck.Label htmlFor="quantity-checking-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.volume_measurement === 'Other'"
                  v-model="form.logistic.volume_measurement_other" type="text" placeholder="Specify" />
              </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm p-6">
              <!-- TODO: tambahkan opsi kapal (kapasitas kapal) di sini — untuk sekarang cuma provide truck -->
              <div class="font-section mb-3 pb-2 border-b border-slate-100">CAPACITY INFO</div>
              <div class="space-y-2">
                <FormCheck v-for="(cap, idx) in ['5 KL', '8 KL', '10 KL', '16 KL']" :key="cap">
                  <FormCheck.Input :id="'capacity-' + idx" type="radio" :value="cap"
                    v-model="form.logistic.max_truck_capacity" />
                  <FormCheck.Label :htmlFor="'capacity-' + idx">{{ cap }}</FormCheck.Label>
                </FormCheck>
                <FormCheck>
                  <FormCheck.Input id="capacity-other" type="radio" value="Other"
                    v-model="form.logistic.max_truck_capacity" />
                  <FormCheck.Label htmlFor="capacity-other">Other,</FormCheck.Label>
                </FormCheck>
                <FormInput v-if="form.logistic.max_truck_capacity === 'Other'"
                  v-model="form.logistic.max_truck_capacity_other" type="text" placeholder="Specify" />
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
             STEP 6 – SUMMARY AND AGREEMENT
             ========================= -->
        <CardSection v-show="step === 6" title="Summary and Agreement" icon="ClipboardCheck" class="mb-8">
          <div class="space-y-6">
            <!-- Summary -->
            <div>
              <div class="font-header mb-2">FORM SUMMARY</div>
              <p class="font-caption mb-3">
                Periksa kembali data di bawah sebelum menyetujui dan mengirim.
              </p>
              <div class="space-y-5">
                <div v-for="section in summarySections" :key="section.title">
                  <div class="font-section mb-3 pb-2 border-b border-slate-100">{{ section.title }}</div>
                  <div class="grid md:grid-cols-2 gap-3 bg-slate-50 rounded p-4">
                    <div v-for="row in section.rows" :key="row.label"
                      class="flex justify-between gap-4 border-b border-slate-200 pb-1">
                      <span class="font-label">{{ row.label }}</span>
                      <span class="font-strong text-right">{{ row.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Agreement -->
            <div>
              <div class="font-header mb-2">AGREEMENT</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Updated By</FormLabel>
                  <FormInput v-model="form.agreement.updated_by" type="text"
                    placeholder="Name of person filling this form"
                    :class="inputClass('agreement.updated_by')" />
                  <small v-if="fieldError('agreement.updated_by')" class="block input-error-text">{{
                    fieldError('agreement.updated_by') }}</small>
                </div>

                <div class="md:col-span-2">
                  <label class="inline-flex items-center gap-3">
                    <input type="checkbox" v-model="form.agreement.agree" />
                    <span class="font-body hover:underline cursor-pointer">
                      Setujui Terms &amp; Conditions: saya menyatakan bahwa data di atas benar adanya (I declare that
                      the above data is true).
                    </span>
                  </label>
                  <small v-if="fieldError('agreement.agree')" class="block input-error-text">{{
                    fieldError('agreement.agree') }}</small>
                </div>
              </div>
            </div>
          </div>
        </CardSection>

        <!-- Navigasi step -->
        <div class="mb-8 flex flex-wrap justify-between gap-2">
          <Button variant="outline-secondary" class="inline-flex items-center gap-2" :disabled="busy || step === 1"
            @click="prev">
            <Lucide icon="ChevronLeft" class="h-4 w-4" />
            Prev
          </Button>
          <Button v-if="step < 6" variant="primary" class="inline-flex items-center gap-2" :disabled="busy"
            @click="next">
            Next
            <Lucide icon="ChevronRight" class="h-4 w-4" />
          </Button>
          <Button v-else variant="primary" class="inline-flex items-center gap-2" :disabled="busy" @click="save">
            <Lucide icon="Check" class="h-4 w-4" />
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </Button>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import { FormInput, FormSelect, FormTextarea, FormLabel, FormCheck } from '@/components/Base/Form'
import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useRegionCascade, type RegionOption } from '@/composables/useRegionCascade'
import logoUrl from '@/assets/images/putih-tulisan-atas.png'
import logoUrl2 from '@/assets/images/tds-crs-new.png'

const route = useRoute()
const router = useRouter()
const { success, error, info } = useNotification()

// Satu-satunya jalur pengisian: link publik /verify/:token
const token = route.params.token as string

const busy = ref(false)
const step = ref(1)
const verificationId = ref<number | null>(null)

/* ====== status lifecycle token dari GET /api/verify/{token} ====== */
const tokenStatus = ref<'active' | 'expired' | 'used' | null>(null)

/* ====== progress indicator (display-only, Stepper) ====== */
const stepTitles = ['Corporate Details', 'Documentation', 'Payment Info', 'Supply Scheme', 'Logistic Info', 'Summary and Agreement']
const stepperItems = computed<StepItem[]>(() =>
  stepTitles.map((title, idx) => {
    const n = idx + 1
    const status: StepItem['status'] = n < step.value ? 'completed' : n === step.value ? 'active' : 'pending'
    return { title, status }
  })
)

/* ====== master options: cascading province -> regency -> district -> village
   (laravel-nusa-address-full-migration Task 8), satu instance terpisah per
   blok alamat (corporate vs registered) supaya opsi masing-masing tidak
   saling menimpa. ====== */
const corporateRegion = useRegionCascade()
const registeredRegion = useRegionCascade()

/* ====== lists ====== */
const typeBusiness = [
  'Agriculture & Forestry / Horticulture', 'Business & Information',
  'Construction / Utilities / Contracting', 'Education', 'Finance & Insurance',
  'Food & hospitality', 'Gaming', 'Health Services', 'Motor Vehicle'
]
const ownershipOpts = ['Affiliation', 'National Private', 'Foreign Private', 'Joint Venture', 'BUMN / BUMD', 'Foundation', 'Personal']
const payMethods = ['Cash', 'Transfer', 'Cheque / Giro', 'Bank Guarantee']
const paymentMethodChoices = [...payMethods, 'Other']
const paymentMethodIcons: Record<string, string> = {
  'Cash': 'Banknote',
  'Transfer': 'ArrowLeftRight',
  'Cheque / Giro': 'FileText',
  'Bank Guarantee': 'ShieldCheck',
  'Other': 'MoreHorizontal',
}
const supplySchemeOptions = ['Delivery', 'Self Pickup']
const incoTermsOptions = [
  { code: 'EXW', label: 'Ex Works' },
  { code: 'FOB', label: 'Free On Board' },
  { code: 'CIF', label: 'Cost, Insurance and Freight' },
  { code: 'CFR', label: 'Cost and Freight' },
  { code: 'DDP', label: 'Delivered Duty Paid' },
  { code: 'DAP', label: 'Delivered At Place' },
  { code: 'FCA', label: 'Free Carrier' },
  { code: 'CPT', label: 'Carriage Paid To' },
]
const quantityCheckingOptions = [
  'Weighbridge (Truck Scale)', 'Platform Scale', 'Volume Measurement', 'Truck Counting',
  'Delivery Order Verification', 'Net Weight Verification', 'Sampling'
]

/* ====== form state ====== */
const form = reactive<any>({
  corporate: {
    nama: '', holding: '', print_product: '',
    email: '', website: '', alamat: '',
    // id_provinsi/id_kabupaten: LEGACY, hidden pass-through — TIDAK ada
    // UI-nya lagi (lihat blok address di template). customers.id_provinsi/
    // id_kabupaten masih NOT NULL di DB (belum dilonggarkan di Fase 2), jadi
    // field ini dipertahankan apa adanya dari data existing customer di
    // load() dan ikut ter-echo balik lewat spread `...form.corporate` di
    // save() supaya update tidak menabrak constraint tsb. Lihat catatan di
    // load() dan laporan Apollo untuk detail — TIDAK diedit user.
    id_provinsi: null as number | null, id_kabupaten: null as number | null,
    // Kolom baru berbasis kode BPS (string), province -> regency -> district
    // -> village, menggantikan dropdown provinsi/kabupaten lama + free-text
    // kecamatan/kelurahan lama.
    province_id: null as string | null, regency_id: null as string | null,
    district_id: null as string | null, village_id: null as string | null,
    postal_code: '', telepon: '', fax: '',
    tipe_bisnis: '', tipe_bisnis_lain: '', ownership: '', ownership_lain: ''
  },
  registered: {
    email: '', alamat: '',
    // customer_payment.prov_billing/kab_billing nullable di DB, jadi alamat
    // billing bisa full cutover ke kolom baru tanpa perlu pass-through
    // legacy id (beda dengan corporate di atas).
    province_id: null as string | null, regency_id: null as string | null,
  },
  delivery: { alamat1: '', alamat2: '', alamat3: '' },
  invoice: { delivery_address: '', pic: { name: '', position: '', telephone: '', mobile: '', email: '' } },
  docs: {
    certificate_number: '', npwp_number: '', nib_number: '', other_doc: '',
    akta_file: null, npwp_file: null, nib_file: null, other_file: null,
    other_files: [] as { name: string }[]
  },
  payment: {
    payment_method: '', payment_method_other: '',
    payment_type: '', currency: '', bank_name: '', account_number: '', bank_address: '',
    has_credit: false, creditor_name: '',
    schedule: '', schedule_other: '', invoice_tax: false, note: ''
  },
  supply: {
    scheme_details: '', volume_per_month: null,
    operational_from: '', operational_to: '', inco_terms: '', inco_terms_other: ''
  },
  logistic: {
    area: '', security_env: '',
    env: '', env_other: '', condition_desc: '',
    storage: '', storage_other: '', storage_desc: '',
    operating_hours: '', operating_hours_other: '',
    quality_density: false, quality_other_enabled: false, quality_other: '',
    volume_measurement: '', volume_measurement_other: '',
    max_truck_capacity: '', max_truck_capacity_other: ''
  },
  agreement: { updated_by: '', agree: false }
})

/* ====== summary (step 6) — ringkasan lengkap, dikelompokkan per step asal field ====== */
/* Resolve nama wilayah dari daftar opsi cascade yang sedang termuat by id. */
function resolveRegionName(id: string | null | undefined, options: RegionOption[]): string {
  if (!id) return '-'
  return options.find((o) => o.id === id)?.name || '-'
}

function qualityCheckingSummary(): string {
  const parts: string[] = []
  if (form.logistic.quality_density) parts.push('Lab Test')
  if (form.logistic.quality_other_enabled) {
    parts.push(form.logistic.quality_other ? `Others (${form.logistic.quality_other})` : 'Others')
  }
  return parts.length ? parts.join(', ') : '-'
}

const summarySections = computed(() => [
  {
    title: 'Corporate Details',
    rows: [
      { label: 'Company Name', value: form.corporate.nama || '-' },
      { label: 'Holding', value: form.corporate.holding || '-' },
      { label: 'Print Product', value: form.corporate.print_product || '-' },
      { label: 'Head Office Email', value: form.corporate.email || '-' },
      { label: 'Website', value: form.corporate.website || '-' },
      { label: 'Head Office Address', value: form.corporate.alamat || '-' },
      {
        label: 'Head Office Province',
        value: resolveRegionName(form.corporate.province_id, corporateRegion.provinces.value),
      },
      {
        label: 'Head Office City/Regency',
        value: resolveRegionName(form.corporate.regency_id, corporateRegion.regencies.value),
      },
      {
        label: 'Head Office District/Kecamatan',
        value: resolveRegionName(form.corporate.district_id, corporateRegion.districts.value),
      },
      {
        label: 'Head Office Sub-District/Kelurahan',
        value: resolveRegionName(form.corporate.village_id, corporateRegion.villages.value),
      },
      { label: 'Postal Code', value: form.corporate.postal_code || '-' },
      { label: 'Telephone', value: form.corporate.telepon || '-' },
      { label: 'Fax', value: form.corporate.fax || '-' },
      { label: 'NPWP Email', value: form.registered.email || '-' },
      { label: 'NPWP Address', value: form.registered.alamat || '-' },
      {
        label: 'NPWP Province (Registered)',
        value: resolveRegionName(form.registered.province_id, registeredRegion.provinces.value),
      },
      {
        label: 'NPWP City/Regency (Registered)',
        value: resolveRegionName(form.registered.regency_id, registeredRegion.regencies.value),
      },
      { label: 'Delivery Address 1', value: form.delivery.alamat1 || '-' },
      { label: 'Delivery Address 2', value: form.delivery.alamat2 || '-' },
      { label: 'Delivery Address 3', value: form.delivery.alamat3 || '-' },
      {
        label: 'Type of Business',
        value: form.corporate.tipe_bisnis === 'Other'
          ? (form.corporate.tipe_bisnis_lain || '-')
          : (form.corporate.tipe_bisnis || '-'),
      },
      {
        label: 'Ownership',
        value: form.corporate.ownership === 'Other'
          ? (form.corporate.ownership_lain || '-')
          : (form.corporate.ownership || '-'),
      },
    ],
  },
  {
    title: 'Documentation',
    rows: [
      { label: 'Certificate Number (Deed)', value: form.docs.certificate_number || '-' },
      { label: 'NPWP Number', value: form.docs.npwp_number || '-' },
      { label: 'NIB Number', value: form.docs.nib_number || '-' },
      {
        label: 'Other Documents',
        value: (form.docs.other_doc || '-')
          + (form.docs.other_files.length > 0 ? ` (${form.docs.other_files.length} file(s) uploaded)` : ''),
      },
    ],
  },
  {
    title: 'Payment Info',
    rows: [
      {
        label: 'Payment Method',
        value: form.payment.payment_method === 'Other'
          ? (form.payment.payment_method_other || 'Other')
          : (form.payment.payment_method || '-'),
      },
      { label: 'Payment Type', value: form.payment.payment_type || '-' },
      { label: 'Currency', value: form.payment.currency || '-' },
      { label: 'Bank Name', value: form.payment.bank_name || '-' },
      { label: 'Account Number', value: form.payment.account_number || '-' },
      { label: 'Bank Address', value: form.payment.bank_address || '-' },
      {
        label: 'Have Credit Facility',
        value: form.payment.has_credit
          ? (form.payment.creditor_name ? `Yes (${form.payment.creditor_name})` : 'Yes')
          : 'No',
      },
      {
        label: 'Submit and Payment Schedule',
        value: form.payment.schedule === 'Other'
          ? (form.payment.schedule_other || '-')
          : (form.payment.schedule || '-'),
      },
      { label: 'Invoices (Tax Invoice)', value: form.payment.invoice_tax ? 'Yes' : 'No' },
      { label: 'Notes', value: form.payment.note || '-' },
      { label: 'PIC Name', value: form.invoice.pic.name || '-' },
      { label: 'PIC Position', value: form.invoice.pic.position || '-' },
      { label: 'PIC Phone', value: form.invoice.pic.telephone || '-' },
      { label: 'PIC Mobile', value: form.invoice.pic.mobile || '-' },
      { label: 'PIC Email', value: form.invoice.pic.email || '-' },
    ],
  },
  {
    title: 'Supply Scheme',
    rows: [
      { label: 'Supply Scheme Details', value: form.supply.scheme_details || '-' },
      {
        label: 'Volume per Month',
        value: form.supply.volume_per_month != null && form.supply.volume_per_month !== ''
          ? String(form.supply.volume_per_month)
          : '-',
      },
      {
        label: 'Operational Hour',
        value: (form.supply.operational_from || form.supply.operational_to)
          ? `${form.supply.operational_from || '-'} - ${form.supply.operational_to || '-'}`
          : '-',
      },
      {
        label: 'Inco Terms',
        value: form.supply.inco_terms === 'Other'
          ? (form.supply.inco_terms_other || '-')
          : (form.supply.inco_terms || '-'),
      },
    ],
  },
  {
    title: 'Logistic Info',
    rows: [
      { label: 'Area / Location Size', value: form.logistic.area || '-' },
      { label: 'Security Environment', value: form.logistic.security_env || '-' },
      {
        label: 'Conditions Around Locations',
        value: form.logistic.env === 'Other'
          ? (form.logistic.env_other || '-')
          : form.logistic.env === 'Industri' ? 'Industrial'
          : form.logistic.env === 'Pemukiman' ? 'Residential'
          : (form.logistic.env || '-'),
      },
      { label: 'Description Of Condition', value: form.logistic.condition_desc || '-' },
      {
        label: 'Storage Facility',
        value: form.logistic.storage === 'Other'
          ? (form.logistic.storage_other || '-')
          : (form.logistic.storage || '-'),
      },
      { label: 'Description Of Storage Facility', value: form.logistic.storage_desc || '-' },
      {
        label: 'Operating Hours',
        value: form.logistic.operating_hours === 'Other'
          ? (form.logistic.operating_hours_other || '-')
          : (form.logistic.operating_hours || '-'),
      },
      { label: 'Quality Checking', value: qualityCheckingSummary() },
      {
        label: 'Quantity Checking',
        value: form.logistic.volume_measurement === 'Other'
          ? (form.logistic.volume_measurement_other || '-')
          : (form.logistic.volume_measurement || '-'),
      },
      {
        label: 'Capacity Info',
        value: form.logistic.max_truck_capacity === 'Other'
          ? (form.logistic.max_truck_capacity_other || '-')
          : (form.logistic.max_truck_capacity || '-'),
      },
    ],
  },
])

/* ====== validasi — useVuelidate (gating hanya saat Simpan di step 6, bukan per-step Next) ====== */
const requiredTrimmed = (message: string) =>
  helpers.withMessage(message, (val: unknown) => !!String(val ?? '').trim().length)

const validationRules = computed(() => ({
  corporate: {
    nama: { required: helpers.withMessage('Nama perusahaan wajib diisi.', required) },
    email: { required: helpers.withMessage('Email kantor pusat wajib diisi.', required) },
    alamat: { required: helpers.withMessage('Alamat kantor pusat wajib diisi.', required) },
    province_id: {
      required: helpers.withMessage('Provinsi kantor pusat wajib diisi.', required),
    },
    regency_id: {
      required: helpers.withMessage('Kabupaten/Kota kantor pusat wajib diisi.', required),
    },
    telepon: { required: helpers.withMessage('Telepon kantor pusat wajib diisi.', required) },
  },
  registered: {
    email: { required: helpers.withMessage('Email Alamat NPWP (Registered) wajib diisi.', required) },
    alamat: { required: helpers.withMessage('Alamat NPWP (Registered) wajib diisi.', required) },
    province_id: {
      required: helpers.withMessage('Provinsi Alamat NPWP (Registered) wajib diisi.', required),
    },
    regency_id: {
      required: helpers.withMessage('Kabupaten/Kota Alamat NPWP (Registered) wajib diisi.', required),
    },
  },
  docs: {
    npwp_number: { required: helpers.withMessage('NPWP Number wajib diisi.', required) },
    nib_number: { required: helpers.withMessage('NIB Number wajib diisi.', required) },
  },
  payment: {
    payment_method: { required: helpers.withMessage('Payment Metode wajib dipilih.', required) },
    schedule: { required: helpers.withMessage('Submit and Payment Schedule wajib dipilih.', required) },
  },
  invoice: {
    pic: {
      name: { required: requiredTrimmed('Nama PIC Invoice wajib diisi.') },
      telephone: { required: requiredTrimmed('Telepon PIC Invoice wajib diisi.') },
      mobile: { required: requiredTrimmed('Mobile/HP PIC Invoice wajib diisi.') },
    },
  },
  supply: {
    scheme_details: { required: helpers.withMessage('Supply Scheme Details wajib dipilih.', required) },
    volume_per_month: { required: helpers.withMessage('Volume per Month wajib diisi.', required) },
    inco_terms: { required: helpers.withMessage('Inco Terms wajib dipilih.', required) },
  },
  agreement: {
    updated_by: { required: requiredTrimmed('Updated By wajib diisi.') },
    agree: {
      mustAgree: helpers.withMessage(
        'Anda wajib menyetujui pernyataan kebenaran data (Terms & Conditions) sebelum submit.',
        (v: boolean) => v === true,
      ),
    },
  },
}))

const v$ = useVuelidate(validationRules, form)

/* Peta field → nomor step, dipakai untuk lompat otomatis ke step pertama yang invalid. */
const FIELD_STEP_MAP: Record<string, number> = {
  'corporate.nama': 1, 'corporate.email': 1, 'corporate.alamat': 1,
  'corporate.province_id': 1, 'corporate.regency_id': 1, 'corporate.telepon': 1,
  'registered.email': 1, 'registered.alamat': 1,
  'registered.province_id': 1, 'registered.regency_id': 1,
  'docs.npwp_number': 2, 'docs.nib_number': 2,
  'payment.payment_method': 3, 'payment.schedule': 3,
  'invoice.pic.name': 3, 'invoice.pic.telephone': 3, 'invoice.pic.mobile': 3,
  'supply.scheme_details': 4, 'supply.volume_per_month': 4, 'supply.inco_terms': 4,
  'agreement.updated_by': 6, 'agreement.agree': 6,
}

/* Helper tampilan error, mirip pola Penawaran/Form.vue tapi mendukung nested path (mis. "corporate.nama"). */
function resolveV(path: string): any {
  return path.split('.').reduce((acc: any, key) => acc?.[key], v$.value as any)
}

function fieldError(path: string): string {
  const f = resolveV(path)
  return f?.$error ? (f.$errors[0]?.$message?.toString() ?? '') : ''
}

function inputClass(path: string): string {
  return resolveV(path)?.$error ? 'input-error' : ''
}

/* Kumpulkan semua pesan error untuk ringkasan sticky. */
function collectErrorMessages(): string[] {
  const msgs: string[] = []
  for (const e of v$.value.$errors) {
    const m = e.$message?.toString()
    if (m && !msgs.includes(m)) msgs.push(m)
  }
  return msgs
}

/* ====== cascading province -> regency -> district -> village ======
   Handler di bawah dipicu murni oleh interaksi user (native @change) —
   BUKAN lewat watch() — supaya tidak race dengan assignment terprogram di
   load()/copyHeadOfficeToRegistered() (pola watch() lama pernah butuh
   urutan assignment yang sangat hati-hati untuk masalah serupa, lihat riwayat
   git file ini). Prefill di load() memakai primeCorporateRegion()/
   primeRegisteredRegion() di bawah, bukan handler ini. */
function onCorporateProvinceChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value || null
  form.corporate.province_id = value
  form.corporate.regency_id = null
  form.corporate.district_id = null
  form.corporate.village_id = null
  corporateRegion.fetchRegencies(value)
}
function onCorporateRegencyChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value || null
  form.corporate.regency_id = value
  form.corporate.district_id = null
  form.corporate.village_id = null
  corporateRegion.fetchDistricts(value)
}
function onCorporateDistrictChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value || null
  form.corporate.district_id = value
  form.corporate.village_id = null
  corporateRegion.fetchVillages(value)
}
function onCorporateVillageChange(e: Event) {
  form.corporate.village_id = (e.target as HTMLSelectElement).value || null
}
function onRegisteredProvinceChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value || null
  form.registered.province_id = value
  form.registered.regency_id = null
  registeredRegion.fetchRegencies(value)
}
function onRegisteredRegencyChange(e: Event) {
  form.registered.regency_id = (e.target as HTMLSelectElement).value || null
}

/* Muat opsi cascade sampai level yang sudah ter-set di form (dipakai setelah
   prefill/load supaya dropdown menampilkan pilihan yang benar). */
async function primeCorporateRegion() {
  if (!form.corporate.province_id) return
  await corporateRegion.fetchRegencies(form.corporate.province_id)
  if (!form.corporate.regency_id) return
  await corporateRegion.fetchDistricts(form.corporate.regency_id)
  if (!form.corporate.district_id) return
  await corporateRegion.fetchVillages(form.corporate.district_id)
}
async function primeRegisteredRegion() {
  if (!form.registered.province_id) return
  await registeredRegion.fetchRegencies(form.registered.province_id)
}

/* ====== shortcut: samakan Alamat NPWP (registered) dengan Alamat Kantor Pusat ======
   Checked → copy dari corporate; unchecked → field registered di-reset kosong. */
const sameAsHeadOffice = ref(false)
async function copyHeadOfficeToRegistered() {
  form.registered.email = form.corporate.email
  form.registered.alamat = form.corporate.alamat
  form.registered.province_id = form.corporate.province_id
  form.registered.regency_id = form.corporate.regency_id
  await primeRegisteredRegion()
}
function resetRegisteredAddress() {
  form.registered.email = ''
  form.registered.alamat = ''
  form.registered.province_id = null
  form.registered.regency_id = null
  registeredRegion.regencies.value = []
}
watch(sameAsHeadOffice, (checked) => { if (checked) copyHeadOfficeToRegistered(); else resetRegisteredAddress() })

/* ====== load data verifikasi ====== */
async function load() {
  const { data } = await axios.get(`/api/verify/${token}`)
  verificationId.value = data.id_verification

  // Tentukan status lifecycle token lebih dulu. Kalau token sudah tidak
  // aktif, jangan render form / load master data lain — cukup tampilkan
  // banner status (lihat template).
  tokenStatus.value = data.status ?? 'active'
  if (tokenStatus.value !== 'active') return

  await corporateRegion.fetchProvinces()
  await registeredRegion.fetchProvinces()

  // Prefill dari relasi customer (pertama kali masuk)
  if (data.customer) {
    const c = data.customer
    form.corporate.nama = c.nama_perusahaan || form.corporate.nama
    form.corporate.email = c.email || form.corporate.email
    form.corporate.alamat = c.alamat_perusahaan || form.corporate.alamat
    form.corporate.telepon = c.telepon || form.corporate.telepon
    form.corporate.fax = c.fax || form.corporate.fax
    form.corporate.postal_code = c.postal_code || form.corporate.postal_code

    // Legacy id_provinsi/id_kabupaten — TIDAK ada UI-nya lagi, cuma
    // dipertahankan apa adanya dari data existing customer supaya update ke
    // `customers` tidak menabrak kolom NOT NULL tsb (lihat catatan di
    // deklarasi form.corporate di atas).
    form.corporate.id_provinsi = c.id_provinsi ?? null
    form.corporate.id_kabupaten = c.id_kabupaten ?? null

    // Kolom baru berbasis kode BPS, kalau customer ini sudah pernah
    // termigrasi (Task 4/5) atau pernah submit lewat form baru ini.
    form.corporate.province_id = c.province_id ?? null
    form.corporate.regency_id = c.regency_id ?? null
    form.corporate.district_id = c.district_id ?? null
    form.corporate.village_id = c.village_id ?? null
  }

  // Prefill dari JSON simpanan bila ada. Snapshot ini bisa datang dari dua
  // skema berbeda tergantung siapa yang terakhir menyimpan:
  // - updateByToken (publik, lama): legal_data{corporate,registered,delivery,docs_meta,agreement},
  //   finance_data{invoice,payment,registered,delivery}, logistik_data{logistic,supply}
  // - updateInternal (internal, baru): legal_data{corporate,registered,delivery,documents,agreement},
  //   finance_data{contacts,payment}, logistik_data{logistik,supply}
  try {
    const legal = JSON.parse(data.legal_data || '{}')
    if (legal.corporate) Object.assign(form.corporate, legal.corporate)
    if (legal.registered) Object.assign(form.registered, legal.registered)
    if (legal.delivery) Object.assign(form.delivery, legal.delivery)
    if (legal.agreement) Object.assign(form.agreement, legal.agreement)

    const docsSrc = legal.documents || legal.docs_meta
    if (docsSrc) {
      form.docs.certificate_number = docsSrc.nomor_sertifikat ?? docsSrc.certificate_number ?? form.docs.certificate_number
      form.docs.npwp_number = docsSrc.nomor_npwp ?? docsSrc.npwp_number ?? form.docs.npwp_number
      form.docs.other_doc = docsSrc.dokumen_lainnya ?? docsSrc.other_doc ?? form.docs.other_doc
    }
    if (legal.corporate) {
      form.docs.nib_number = legal.corporate.nib_number ?? form.docs.nib_number
      form.docs.nib_file = legal.corporate.nib_file ?? form.docs.nib_file
    }
  } catch { }
  try {
    const finance = JSON.parse(data.finance_data || '{}')
    if (finance.payment) Object.assign(form.payment, finance.payment)
    if (finance.registered) Object.assign(form.registered, finance.registered)
    if (finance.delivery) Object.assign(form.delivery, finance.delivery)

    // skema lama: finance.invoice = { delivery_address, pic }
    if (finance.invoice) Object.assign(form.invoice, finance.invoice)

    // skema baru: finance.contacts = { pic_invoice, invoice_delivery_addr_primary, ... }
    const picInvoice = finance.contacts?.pic_invoice
    if (picInvoice) {
      form.invoice.pic.name = picInvoice.name ?? form.invoice.pic.name
      form.invoice.pic.position = picInvoice.position ?? form.invoice.pic.position
      form.invoice.pic.telephone = picInvoice.telp ?? form.invoice.pic.telephone
      form.invoice.pic.mobile = picInvoice.mobile ?? form.invoice.pic.mobile
      form.invoice.pic.email = picInvoice.email ?? form.invoice.pic.email
    }
    if (finance.contacts?.invoice_delivery_addr_primary) {
      form.invoice.delivery_address = finance.contacts.invoice_delivery_addr_primary
    }
  } catch { }
  try {
    const logistik = JSON.parse(data.logistik_data || '{}')
    const lg = logistik.logistik || logistik.logistic
    if (lg) Object.assign(form.logistic, lg)
    if (logistik.supply) Object.assign(form.supply, logistik.supply)
  } catch { }

  // Muat opsi cascade sampai level yang sudah ter-set (dari relasi customer
  // dan/atau override snapshot JSON di atas), supaya dropdown menampilkan
  // pilihan yang benar saat form pertama kali dirender.
  await primeCorporateRegion()
  await primeRegisteredRegion()
}
onMounted(load)

/* ====== theme fix: halaman publik ini sibling dari Layout.vue, jadi ThemeSwitcher.vue
   (satu-satunya komponen yang nempelin class `.theme-1` ke <html>) tidak pernah mount di
   sini. Tanpa class itu token warna (--color-theme-1) jatuh ke default :root = biru. ====== */
onMounted(() => { document.documentElement.classList.add('theme-1') })
onUnmounted(() => { document.documentElement.classList.remove('theme-1') })

/* ====== file upload (opsional) ====== */
// FileUploadField bekerja dengan v-model File|null. Field docs.* dipakai juga
// untuk menyimpan string path setelah berhasil diupload (lihat upload()) —
// computed ini memastikan picker hanya menampilkan value-nya kalau memang
// masih berupa File yang belum diupload, tanpa mengubah nilai string tersimpan.
function toFileModel(key: 'akta_file' | 'npwp_file' | 'nib_file') {
  return computed<File | null>({
    get: () => (form.docs[key] instanceof File ? form.docs[key] : null),
    set: (value) => { form.docs[key] = value }
  })
}
const aktaFileModel = toFileModel('akta_file')
const npwpFileModel = toFileModel('npwp_file')
const nibFileModel = toFileModel('nib_file')
const otherFileModel = computed<File[] | null>({
  get: () => (Array.isArray(form.docs.other_file) ? form.docs.other_file : null),
  set: (value) => { form.docs.other_file = value }
})

async function upload(key: string) {
  const file = form.docs[key]
  if (!(file instanceof File)) { info('Info', 'Pilih file dulu'); return }
  const fd = new FormData()
  fd.append('file', file)
  fd.append('field', key)
  try {
    busy.value = true
    const { data } = await axios.post(`/api/customer-verifications/${verificationId.value}/upload`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (key === 'nib_file') {
      form.docs.nib_file = data?.path ?? data?.file_path ?? data?.data?.path ?? data?.data?.file_path ?? data?.url ?? file.name
    }
    success('Berhasil', 'File diupload')
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal upload')
  } finally {
    busy.value = false
  }
}

async function uploadOtherFile() {
  const file = form.docs.other_file
  if (!(file instanceof File)) { info('Info', 'Pilih file dulu'); return }
  const fd = new FormData()
  fd.append('file', file)
  fd.append('field', 'other_file')
  try {
    busy.value = true
    await axios.post(`/api/customer-verifications/${verificationId.value}/upload`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    form.docs.other_files.push({ name: file.name })
    form.docs.other_file = null
    success('Berhasil', 'File diupload')
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal upload')
  } finally {
    busy.value = false
  }
}

/* ====== nav ====== */
function next() { if (step.value < 6) step.value++ }
function prev() { if (step.value > 1) step.value-- }

/* ====== save ====== */
async function save() {
  // Validasi hanya digate di sini (klik Simpan pada step 6), next()/prev() tetap bebas jalan.
  const valid = await v$.value.$validate()
  if (!valid) {
    error('Validasi Gagal', 'Periksa kembali isian berikut:', {
      items: collectErrorMessages(),
      sticky: true,
    })

    const failingSteps = v$.value.$errors
      .map((e: any) => FIELD_STEP_MAP[e.$propertyPath as string])
      .filter((n: number | undefined): n is number => typeof n === 'number')
    if (failingSteps.length) step.value = Math.min(...failingSteps)

    return
  }

  try {
    busy.value = true

    const payload: any = {
      legal_data: JSON.stringify({
        corporate: {
          ...form.corporate,
          nib_number: form.docs.nib_number,
          nib_file: typeof form.docs.nib_file === 'string' ? form.docs.nib_file : null,
        },
        registered: form.registered,
        delivery: form.delivery,
        docs_meta: {
          certificate_number: form.docs.certificate_number,
          npwp_number: form.docs.npwp_number,
          other_doc: form.docs.other_doc,
        },
        agreement: form.agreement
      }),
      finance_data: JSON.stringify({
        invoice: form.invoice,
        payment: form.payment,
        registered: form.registered,
        delivery: form.delivery
      }),
      logistik_data: JSON.stringify({
        logistic: form.logistic,
        supply: form.supply
      }),
      is_reviewed: 0,
      is_evaluated: 0
    }

    await axios.put(`/api/verify/${token}`, payload)
    success('Tersimpan', 'Data verifikasi berhasil disimpan.')
    tokenStatus.value = 'used'
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal menyimpan')
  } finally {
    busy.value = false
  }
}
</script>

<style scoped>
.btn {
  @apply inline-flex items-center px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700;
}
</style>
