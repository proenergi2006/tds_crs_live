<!-- src/views/CustomerVerification/CustomerUpdateForm.vue -->
<template>
  <div class="min-h-screen bg-slate-50">
    <!-- BRAND BAR -->
    <div class="bg-emerald-700 text-white">
      <div class="max-w-6xl mx-auto px-5 py-4">
        <div class="text-2xl font-semibold">CRUSHEDSTONE TRI DAYA SELARAS</div>
        <div class="text-white/80 text-sm">Update Data Customer</div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-5 py-6">
      <!-- Token sudah kedaluwarsa -->
      <Alert v-if="tokenStatus === 'expired'" variant="soft-danger" class="mb-6">
        Link verifikasi ini sudah kedaluwarsa. Silakan hubungi tim marketing TDS untuk mendapatkan link baru.
      </Alert>

      <!-- Token sudah pernah dipakai submit -->
      <Alert v-else-if="tokenStatus === 'used'" variant="soft-success" class="mb-6">
        Terima kasih, data verifikasi untuk perusahaan Anda sudah pernah dikirim sebelumnya. Tidak ada tindakan lebih lanjut yang diperlukan.
      </Alert>

      <template v-else>
        <!-- Progress indicator (display-only) -->
        <div class="mb-6 overflow-x-auto rounded-lg bg-white p-4 shadow-sm">
          <div class="min-w-[720px]">
            <Stepper :steps="stepperItems" direction="horizontal" size="sm" show-label />
          </div>
        </div>

        <!-- Navigasi step -->
        <div class="mb-6 flex flex-wrap justify-end gap-2">
          <button class="px-3 py-2 rounded border" @click="goBack">Kembali</button>
          <button v-if="step > 1" class="px-3 py-2 rounded border" :disabled="busy" @click="prev">Prev</button>
          <button v-if="step < 6" class="px-3 py-2 rounded border bg-emerald-600 text-white hover:bg-emerald-700"
                  :disabled="busy" @click="next">
            Next
          </button>
          <button v-else class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                  :disabled="busy" @click="save">
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </button>
        </div>

        <!-- =========================
             STEP 1 – CORPORATE DETAILS
             ========================= -->
        <CardSection v-show="step === 1" title="Corporate Details" icon="Building2" class="mb-8">
          <div class="space-y-5">
            <div>
              <FormLabel class="font-label !mb-1 block">FULL REGISTERED COMPANY NAME <RequiredAsterisk /></FormLabel>
              <FormInput v-model="form.corporate.nama" type="text" placeholder="Nama perusahaan" />
            </div>

            <div>
              <FormLabel class="font-label !mb-1 block">HOLDING (Jika ada)</FormLabel>
              <FormInput v-model="form.corporate.holding" type="text" />
            </div>

            <div>
              <FormLabel class="font-label !mb-1 block">Cetakan Product</FormLabel>
              <FormInput v-model="form.corporate.print_product" type="text" />
            </div>

            <!-- Address of Head Office -->
            <div class="border-t pt-4">
              <div class="font-section mb-2">ADDRESS OF HEAD OFFICE</div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Email <RequiredAsterisk /></FormLabel>
                  <FormInput v-model="form.corporate.email" type="email" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Website</FormLabel>
                  <FormInput v-model="form.corporate.website" type="text" />
                </div>

                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Address <RequiredAsterisk /></FormLabel>
                  <FormTextarea v-model="form.corporate.alamat" rows="3" />
                </div>

                <!-- Provinsi -->
                <div>
                  <FormLabel class="font-label !mb-1 block">Province / Provinsi <RequiredAsterisk /></FormLabel>

                  <!-- select jika master ada, kalau tidak fallback ke input -->
                  <FormSelect v-if="provinsiOptions.length" v-model="form.corporate.id_provinsi">
                    <option :value="null" disabled>Pilih provinsi</option>
                    <option v-for="p in provinsiOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </FormSelect>
                  <FormInput v-else v-model="form.corporate.provinsi_text" type="text" placeholder="Provinsi" />
                </div>

                <!-- Kabupaten -->
                <div>
                  <FormLabel class="font-label !mb-1 block">City / Kota (Kabupaten) <RequiredAsterisk /></FormLabel>

                  <FormSelect v-if="kabupatenOptions.length" v-model="form.corporate.id_kabupaten"
                              :disabled="!form.corporate.id_provinsi">
                    <option :value="null" disabled>Pilih kabupaten/kota</option>
                    <option v-for="k in kabupatenOptions" :key="k.id" :value="k.id">{{ k.name }}</option>
                  </FormSelect>
                  <FormInput v-else v-model="form.corporate.kabupaten_text" type="text" placeholder="Kota/Kabupaten" />
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">Districts / Kecamatan</FormLabel>
                  <FormInput v-model="form.corporate.kecamatan" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Sub-Districts / Kelurahan</FormLabel>
                  <FormInput v-model="form.corporate.kelurahan" type="text" />
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">Postal Code</FormLabel>
                  <FormInput v-model="form.corporate.postal_code" type="text" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <FormLabel class="font-label !mb-1 block">Telephone <RequiredAsterisk /></FormLabel>
                    <FormInput v-model="form.corporate.telepon" type="text" />
                  </div>
                  <div>
                    <FormLabel class="font-label !mb-1 block">Fax</FormLabel>
                    <FormInput v-model="form.corporate.fax" type="text" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Registered Street Address -->
            <div class="border-t pt-4">
              <div class="font-section mb-2">REGISTERED STREET ADDRESS (NPWP Address)</div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Email <RequiredAsterisk /></FormLabel>
                  <FormInput v-model="form.registered.email" type="email" />
                </div>

                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Address <RequiredAsterisk /></FormLabel>
                  <FormTextarea v-model="form.registered.alamat" rows="3" />
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">Province / Provinsi <RequiredAsterisk /></FormLabel>
                  <FormSelect v-if="provinsiOptions.length" v-model="form.registered.id_provinsi">
                    <option :value="null" disabled>Pilih provinsi</option>
                    <option v-for="p in provinsiOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </FormSelect>
                  <FormInput v-else v-model="form.registered.provinsi_text" type="text" placeholder="Provinsi" />
                </div>

                <div>
                  <FormLabel class="font-label !mb-1 block">City / Kabupaten <RequiredAsterisk /></FormLabel>
                  <FormSelect v-if="registeredKabupatenOptions.length" v-model="form.registered.id_kabupaten"
                              :disabled="!form.registered.id_provinsi">
                    <option :value="null" disabled>Pilih kabupaten/kota</option>
                    <option v-for="k in registeredKabupatenOptions" :key="k.id" :value="k.id">{{ k.name }}</option>
                  </FormSelect>
                  <FormInput v-else v-model="form.registered.kabupaten_text" type="text" placeholder="Kota/Kabupaten" />
                </div>
              </div>
            </div>

            <!-- Product Delivery Address -->
            <div class="border-t pt-4">
              <div class="font-section mb-2">PRODUCT DELIVERY FULL ADDRESS OR SITE ADDRESS</div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Alamat 1</FormLabel>
                  <FormTextarea v-model="form.delivery.alamat1" rows="3" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Alamat 2</FormLabel>
                  <FormTextarea v-model="form.delivery.alamat2" rows="3" />
                </div>
                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Alamat 3</FormLabel>
                  <FormTextarea v-model="form.delivery.alamat3" rows="3" />
                </div>
              </div>
            </div>

            <!-- Type of Business + Ownership -->
            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-section mb-2">TYPE OF BUSINESS</div>
                <div class="space-y-2">
                  <div v-for="opt in typeBusiness" :key="opt" class="flex items-center gap-2">
                    <input type="radio" :value="opt" v-model="form.corporate.tipe_bisnis" />
                    <span class="font-body">{{ opt }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.corporate.tipe_bisnis" />
                    <span class="font-body">Other,</span>
                  </div>
                  <FormInput v-model="form.corporate.tipe_bisnis_lain" type="text" placeholder="Specify" />
                </div>
              </div>

              <div>
                <div class="font-section mb-2">OWNERSHIP</div>
                <div class="space-y-2">
                  <div v-for="opt in ownershipOpts" :key="opt" class="flex items-center gap-2">
                    <input type="radio" :value="opt" v-model="form.corporate.ownership" />
                    <span class="font-body">{{ opt }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.corporate.ownership" />
                    <span class="font-body">Other,</span>
                  </div>
                  <FormInput v-model="form.corporate.ownership_lain" type="text" placeholder="Specify" />
                </div>
              </div>
            </div>
          </div>
        </CardSection>

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
                <button class="btn mt-2" @click="upload('akta_file')">Upload</button>
              </div>

              <!-- NPWP -->
              <div>
                <FormLabel class="font-label !mb-1 block">NPWP Number <RequiredAsterisk /></FormLabel>
                <FormInput v-model="form.docs.npwp_number" type="text" class="mb-2" />
                <FileUploadField v-model="npwpFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
                <button class="btn mt-2" @click="upload('npwp_file')">Upload</button>
              </div>

              <!-- NIB -->
              <div>
                <FormLabel class="font-label !mb-1 block">NIB Number <RequiredAsterisk /></FormLabel>
                <FormInput v-model="form.docs.nib_number" type="text" class="mb-2" />
                <FileUploadField v-model="nibFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
                <button class="btn mt-2" @click="upload('nib_file')">Upload</button>
              </div>

              <!-- Dokumen lain (multiple: surat keterangan domisili, fixed asset, dll) -->
              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">Dokumen Lainnya (Surat Keterangan Domisili, Fixed Asset, dll)</FormLabel>
                <FormInput v-model="form.docs.other_doc" type="text" placeholder="Keterangan dokumen" class="mb-2" />

                <ul v-if="form.docs.other_files.length" class="mt-2 mb-2 space-y-1 text-sm text-slate-600 list-disc list-inside">
                  <li v-for="(f, idx) in form.docs.other_files" :key="idx">{{ f.name }}</li>
                </ul>

                <FileUploadField v-model="otherFileModel" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" />
                <button class="btn mt-2" :disabled="busy" @click="uploadOtherFile">Tambah File</button>
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
        <CardSection v-show="step === 3" title="Payment Info" icon="Wallet" class="mb-8">
          <div class="space-y-6">
            <!-- Payment Metode -->
            <div>
              <div class="mb-2 flex items-center gap-1 font-section">
                PAYMENT METODE <RequiredAsterisk />
              </div>
              <div class="space-y-2">
                <label class="flex items-center gap-2" v-for="m in payMethods" :key="m">
                  <input type="radio" :value="m" v-model="form.payment.payment_method">
                  <span class="font-body">{{ m }}</span>
                </label>
                <div class="flex items-center gap-2">
                  <input type="radio" value="Other" v-model="form.payment.payment_method">
                  <span class="font-body">Other,</span>
                </div>
                <FormInput v-model="form.payment.payment_method_other" type="text" placeholder="Specify" />
              </div>
            </div>

            <!-- Payment Term -->
            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <FormLabel class="font-label !mb-1 block">Payment Type</FormLabel>
                <FormSelect v-model="form.payment.payment_type">
                  <option value="">Pilih Payment Type</option>
                  <option value="CASH">CASH</option>
                  <option value="CREDIT">CREDIT</option>
                </FormSelect>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Currency / Mata Uang</FormLabel>
                <FormInput v-model="form.payment.currency" type="text" />
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Bank Name / Nama Bank</FormLabel>
                <FormInput v-model="form.payment.bank_name" type="text" />
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">Account Number / Nomor Rekening</FormLabel>
                <FormInput v-model="form.payment.account_number" type="text" />
              </div>
              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">Bank Address / Alamat Bank</FormLabel>
                <FormTextarea v-model="form.payment.bank_address" rows="3" />
              </div>

              <div class="md:col-span-2">
                <div class="font-section mb-2">Have Credit Facility or Bank Loan?</div>
                <div class="flex items-center gap-5">
                  <label class="flex items-center gap-2">
                    <input type="radio" :value="true" v-model="form.payment.has_credit" />
                    <span class="font-body">Yes</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" :value="false" v-model="form.payment.has_credit" />
                    <span class="font-body">No</span>
                  </label>
                </div>
              </div>

              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">Nama Penyedia Kredit atau Pinjaman</FormLabel>
                <FormInput v-model="form.payment.creditor_name" type="text" placeholder="Specify" />
              </div>
            </div>

            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <div class="mb-2 flex items-center gap-1 font-section">
                  SUBMIT AND PAYMENT SCHEDULE <RequiredAsterisk />
                </div>
                <div class="flex flex-wrap items-center gap-4">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Every Day" v-model="form.payment.schedule">
                    <span class="font-body">Every Day</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.payment.schedule">
                    <span class="font-body">Other</span>
                  </label>
                  <FormInput
                    v-if="form.payment.schedule === 'Other'"
                    v-model="form.payment.schedule_other"
                    type="text"
                    class="flex-1 min-w-[160px]"
                    placeholder="Specify"
                  />
                </div>
              </div>

              <div>
                <div class="font-section mb-2">INVOICES</div>
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" v-model="form.payment.invoice_tax" />
                  <span class="font-body">Tax Invoice (Faktur Pajak)</span>
                </label>
              </div>

              <div class="md:col-span-2">
                <FormLabel class="font-label !mb-1 block">KETERANGAN (Jika Ada)</FormLabel>
                <FormTextarea v-model="form.payment.note" rows="3" />
              </div>
            </div>

            <!-- PIC Invoice -->
            <div class="border-t pt-4">
              <div class="mb-1 flex items-center gap-1 font-section">
                PIC INVOICE <RequiredAsterisk />
              </div>
              <p class="font-caption mb-3">
                Nama, Telepon, dan Mobile PIC Invoice wajib diisi sebelum submit.
              </p>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <FormLabel class="font-label !mb-1 block">Nama PIC <RequiredAsterisk /></FormLabel>
                  <FormInput v-model="form.invoice.pic.name" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Jabatan</FormLabel>
                  <FormInput v-model="form.invoice.pic.position" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Telepon <RequiredAsterisk /></FormLabel>
                  <FormInput v-model="form.invoice.pic.telephone" type="text" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Mobile / HP <RequiredAsterisk /></FormLabel>
                  <FormInput v-model="form.invoice.pic.mobile" type="text" />
                </div>
                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Email</FormLabel>
                  <FormInput v-model="form.invoice.pic.email" type="email" />
                </div>
              </div>
            </div>
          </div>
        </CardSection>

        <!-- =========================
             STEP 4 – SUPPLY SCHEME
             ========================= -->
        <CardSection v-show="step === 4" title="Supply Scheme" icon="Truck" class="mb-8">
          <div class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <FormLabel class="font-label !mb-1 block">SUPPLY SCHEME DETAILS <RequiredAsterisk /></FormLabel>
                <FormSelect v-model="form.supply.scheme_details">
                  <option value="">Pilih salah satu</option>
                  <option v-for="opt in supplySchemeOptions" :key="opt" :value="opt">{{ opt }}</option>
                </FormSelect>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">VOLUME PER MONTH <RequiredAsterisk /></FormLabel>
                <FormInput v-model="form.supply.volume_per_month" type="number" min="0" />
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <FormLabel class="font-label !mb-1 block">OPERATONAL HOUR – FROM</FormLabel>
                  <FormInput v-model="form.supply.operational_from" type="time" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">TO</FormLabel>
                  <FormInput v-model="form.supply.operational_to" type="time" />
                </div>
              </div>
              <div>
                <FormLabel class="font-label !mb-1 block">INCO TERMS <RequiredAsterisk /></FormLabel>
                <FormSelect v-model="form.supply.inco_terms">
                  <option value="">Pilih salah satu</option>
                  <option v-for="opt in incoTermsOptions" :key="opt" :value="opt">{{ opt }}</option>
                </FormSelect>
              </div>
            </div>
          </div>
        </CardSection>

        <!-- =========================
             STEP 5 – LOGISTIC
             ========================= -->
        <CardSection v-show="step === 5" title="Logistic Info" icon="PackageCheck" class="mb-8">
          <div class="space-y-6">
            <div>
              <div class="font-section mb-2">DESCRIPTION AND CAPACITY OF FACILITIES BUSINESS LOCATION</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <FormLabel class="font-label !mb-1 block">Area (Luas Lokasi)</FormLabel>
                  <FormTextarea v-model="form.logistic.area" rows="3" />
                </div>
                <div>
                  <FormLabel class="font-label !mb-1 block">Security Environment / Business Area</FormLabel>
                  <FormTextarea v-model="form.logistic.security_env" rows="3" />
                </div>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-section mb-2">Conditions Around Locations</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Industri" v-model="form.logistic.env" />
                    <span class="font-body">Industri</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Pemukiman" v-model="form.logistic.env" />
                    <span class="font-body">Pemukiman</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.env" />
                    <span class="font-body">Other,</span>
                  </label>
                  <FormInput v-model="form.logistic.env_other" type="text" placeholder="Specify" />
                </div>

                <div class="mt-4">
                  <FormLabel class="font-label !mb-1 block">Description Of Condition</FormLabel>
                  <FormTextarea v-model="form.logistic.condition_desc" rows="3" />
                </div>
              </div>

              <div>
                <div class="font-section mb-2">Storage Facility</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Indoor" v-model="form.logistic.storage" />
                    <span class="font-body">Indoor</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Outdoor" v-model="form.logistic.storage" />
                    <span class="font-body">Outdoor</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.storage" />
                    <span class="font-body">Other,</span>
                  </label>
                  <FormInput v-model="form.logistic.storage_other" type="text" placeholder="Specify" />
                </div>

                <div class="mt-4">
                  <FormLabel class="font-label !mb-1 block">Description Of Storage Facility</FormLabel>
                  <FormTextarea v-model="form.logistic.storage_desc" rows="3" />
                </div>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-section mb-2">LOGISTICS DETAIL – Operating Hours</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="08.00 - 17.00" v-model="form.logistic.operating_hours">
                    <span class="font-body">08.00 - 17.00</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="24 Hours" v-model="form.logistic.operating_hours">
                    <span class="font-body">24 Hours</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.operating_hours">
                    <span class="font-body">Other,</span>
                  </label>
                  <FormInput v-model="form.logistic.operating_hours_other" type="text" placeholder="Specify" />
                </div>
              </div>

              <div>
                <div class="font-section mb-2">Quality Checking</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.logistic.quality_density">
                    <span class="font-body">UjiLab</span>
                  </label>
                  <div class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.logistic.quality_other_enabled">
                    <span class="font-body">Others,</span>
                  </div>
                  <FormInput v-model="form.logistic.quality_other" type="text" placeholder="Specify" />
                </div>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-section mb-2">Quantity Checking</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2" v-for="opt in quantityCheckingOptions" :key="opt">
                    <input type="radio" :value="opt" v-model="form.logistic.volume_measurement">
                    <span class="font-body">{{ opt }}</span>
                  </label>
                </div>
              </div>

              <div>
                <!-- TODO: tambahkan opsi kapal (kapasitas kapal) di sini — untuk sekarang cuma provide truck -->
                <div class="font-section mb-2">Capacity Info</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2" v-for="cap in ['5 KL','8 KL','10 KL','16 KL']" :key="cap">
                    <input type="radio" :value="cap" v-model="form.logistic.max_truck_capacity">
                    <span class="font-body">{{ cap }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </CardSection>

        <!-- =========================
             STEP 6 – SUMMARY AND AGREEMENT
             ========================= -->
        <CardSection v-show="step === 6" title="Summary and Agreement" icon="ClipboardCheck" class="mb-8">
          <div class="space-y-6">
            <!-- Summary -->
            <div>
              <div class="font-header mb-2">RINGKASAN ISIAN FORM</div>
              <p class="font-caption mb-3">
                Periksa kembali data di bawah sebelum menyetujui dan mengirim.
              </p>
              <div class="grid md:grid-cols-2 gap-3 bg-slate-50 rounded p-4">
                <div
                  v-for="row in summaryRows"
                  :key="row.label"
                  class="flex justify-between gap-4 border-b border-slate-200 pb-1"
                >
                  <span class="font-label">{{ row.label }}</span>
                  <span class="font-strong text-right">{{ row.value }}</span>
                </div>
              </div>
            </div>

            <!-- Agreement -->
            <div>
              <div class="font-header mb-2">AGREEMENT</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <FormLabel class="font-label !mb-1 block">Updated By</FormLabel>
                  <FormInput v-model="form.agreement.updated_by" type="text" placeholder="Nama pengisi form" />
                </div>

                <div class="md:col-span-2">
                  <label class="inline-flex items-center gap-3">
                    <input type="checkbox" v-model="form.agreement.agree" />
                    <span class="font-body">
                      Setujui Terms &amp; Conditions: saya menyatakan bahwa data di atas benar adanya (I declare that the above data is true).
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </CardSection>
      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import { FormInput, FormSelect, FormTextarea, FormLabel } from '@/components/Base/Form'
import Alert from '@/components/Base/Alert'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const route = useRoute()
const router = useRouter()
const { success, error, warning, info } = useNotification()

// Satu-satunya jalur pengisian: link publik /verify/:token
const token = route.params.token as string

const busy = ref(false)
const step = ref(1)
const verificationId = ref<number|null>(null)

/* ====== status lifecycle token dari GET /api/verify/{token} ====== */
const tokenStatus = ref<'active'|'expired'|'used'|null>(null)

/* ====== progress indicator (display-only, Stepper) ====== */
const stepTitles = ['Corporate Details', 'Documentation', 'Payment Info', 'Supply Scheme', 'Logistic Info', 'Summary and Agreement']
const stepperItems = computed<StepItem[]>(() =>
  stepTitles.map((title, idx) => {
    const n = idx + 1
    const status: StepItem['status'] = n < step.value ? 'completed' : n === step.value ? 'active' : 'pending'
    return { title, status }
  })
)

/* ====== master options ====== */
type Opt = { id:number; name:string }
const provinsiOptions = ref<Opt[]>([])
const kabupatenOptions = ref<Opt[]>([])
const registeredKabupatenOptions = ref<Opt[]>([])

/* ====== lists ====== */
const typeBusiness = [
  'Agriculture & Forestry / Horticulture','Business & Information',
  'Construction / Utilities / Contracting','Education','Finance & Insurance',
  'Food & hospitality','Gaming','Health Services','Motor Vehicle'
]
const ownershipOpts = ['Affiliation','National Private','Foreign Private','Joint Venture','BUMN / BUMD','Foundation','Personal']
const payMethods    = ['Cash','Transfer','Cheque / Giro','Bank Guarantee']
const supplySchemeOptions = ['Delivery','Self Pickup']
const incoTermsOptions = ['EXW','FOB','CIF','CFR','DDP','DAP','FCA','CPT']
const quantityCheckingOptions = [
  'Weighbridge (Truck Scale)','Platform Scale','Volume Measurement','Truck Counting',
  'Delivery Order Verification','Net Weight Verification','Sampling'
]

/* ====== form state ====== */
const form = reactive<any>({
  corporate: {
    nama:'', holding:'', print_product:'',
    email:'', website:'', alamat:'',
    id_provinsi:null as number|null, id_kabupaten:null as number|null,
    provinsi_text:'', kabupaten_text:'',
    kecamatan:'', kelurahan:'', postal_code:'', telepon:'', fax:'',
    tipe_bisnis:'', tipe_bisnis_lain:'', ownership:'', ownership_lain:''
  },
  registered: {
    email:'', alamat:'',
    id_provinsi:null as number|null, id_kabupaten:null as number|null,
    provinsi_text:'', kabupaten_text:''
  },
  delivery: { alamat1:'', alamat2:'', alamat3:'' },
  invoice:  { delivery_address:'', pic:{ name:'', position:'', telephone:'', mobile:'', email:'' } },
  docs: { certificate_number:'', npwp_number:'', nib_number:'', other_doc:'',
          akta_file:null, npwp_file:null, nib_file:null, other_file:null,
          other_files: [] as { name:string }[] },
  payment: {
    payment_method:'', payment_method_other:'',
    payment_type:'', currency:'', bank_name:'', account_number:'', bank_address:'',
    has_credit:false, creditor_name:'',
    schedule:'', schedule_other:'', invoice_tax:false, note:''
  },
  supply: { scheme_details:'', volume_per_month:null,
            operational_from:'', operational_to:'', inco_terms:'' },
  logistic: {
    area:'', security_env:'',
    env:'', env_other:'', condition_desc:'',
    storage:'', storage_other:'', storage_desc:'',
    operating_hours:'', operating_hours_other:'',
    quality_density:false, quality_other_enabled:false, quality_other:'',
    volume_measurement:'',
    max_truck_capacity:''
  },
  agreement: { updated_by:'', agree:false }
})

/* ====== summary (step 6) ====== */
const summaryRows = computed(() => [
  { label: 'Nama Perusahaan', value: form.corporate.nama || '-' },
  { label: 'Alamat Kantor Pusat', value: form.corporate.alamat || '-' },
  { label: 'Telepon', value: form.corporate.telepon || '-' },
  { label: 'Email', value: form.corporate.email || '-' },
  { label: 'NIB', value: form.docs.nib_number || '-' },
  {
    label: 'Metode Pembayaran',
    value: form.payment.payment_method === 'Other'
      ? (form.payment.payment_method_other || 'Other')
      : (form.payment.payment_method || '-')
  },
  { label: 'Tipe Pembayaran', value: form.payment.payment_type || '-' },
  { label: 'PIC Invoice', value: form.invoice.pic.name || '-' },
  { label: 'Supply Scheme', value: form.supply.scheme_details || '-' },
  { label: 'Volume per Bulan', value: form.supply.volume_per_month != null && form.supply.volume_per_month !== '' ? String(form.supply.volume_per_month) : '-' },
  { label: 'Inco Terms', value: form.supply.inco_terms || '-' },
  { label: 'Quantity Checking', value: form.logistic.volume_measurement || '-' },
  { label: 'Capacity Info', value: form.logistic.max_truck_capacity || '-' },
])

/* ====== helpers ====== */
function normalizeList(raw:any): Opt[] {
  const arr = Array.isArray(raw?.data) ? raw.data : (Array.isArray(raw)?raw:[])
  return arr.map((x:any)=>({
    id:   x.id ?? x.id_provinsi ?? x.id_kabupaten,
    name: x.name ?? x.nama_provinsi ?? x.nama_kabupaten
  }))
}

/* ====== load masters (support beberapa endpoint) ====== */
async function loadProvinsis(){
  try {
    let data
    try { ({data} = await axios.get('/api/masters/provinsis')) }        // jika punya endpoint masters publik
    catch { ({data} = await axios.get('/api/provinsis')) }               // fallback ke resource standar (butuh auth)
    provinsiOptions.value = normalizeList(data)
  } catch { provinsiOptions.value = [] }
}
async function loadKabupatens(provId:number|null){
  kabupatenOptions.value = []
  if (!provId) return
  try {
    let data
    try { ({data} = await axios.get('/api/masters/kabupatens', { params:{ provinsi_id:provId } })) }
    catch { ({data} = await axios.get('/api/kabupatens', { params:{ provinsi_id:provId } })) }
    kabupatenOptions.value = normalizeList(data)
  } catch { kabupatenOptions.value = [] }
}
async function loadRegisteredKabupatens(provId:number|null){
  registeredKabupatenOptions.value = []
  if (!provId) return
  try {
    let data
    try { ({data} = await axios.get('/api/masters/kabupatens', { params:{ provinsi_id:provId } })) }
    catch { ({data} = await axios.get('/api/kabupatens', { params:{ provinsi_id:provId } })) }
    registeredKabupatenOptions.value = normalizeList(data)
  } catch { registeredKabupatenOptions.value = [] }
}

/* ====== watchers master ====== */
watch(() => form.corporate.id_provinsi, (v)=>{ form.corporate.id_kabupaten=null; loadKabupatens(v) })
watch(() => form.registered.id_provinsi, (v)=>{ form.registered.id_kabupaten=null; loadRegisteredKabupatens(v) })

/* ====== load data verifikasi ====== */
async function load(){
  const { data } = await axios.get(`/api/verify/${token}`)
  verificationId.value = data.id_verification

  // Tentukan status lifecycle token lebih dulu. Kalau token sudah tidak
  // aktif, jangan render form / load master data lain — cukup tampilkan
  // banner status (lihat template).
  tokenStatus.value = data.status ?? 'active'
  if (tokenStatus.value !== 'active') return

  await loadProvinsis()

  // Prefill dari relasi customer (pertama kali masuk)
  if (data.customer) {
    const c = data.customer
    form.corporate.nama        = c.nama_perusahaan || form.corporate.nama
    form.corporate.email       = c.email || form.corporate.email
    form.corporate.alamat      = c.alamat_perusahaan || form.corporate.alamat
    form.corporate.telepon     = c.telepon || form.corporate.telepon
    form.corporate.fax         = c.fax || form.corporate.fax
    form.corporate.postal_code = c.postal_code || form.corporate.postal_code

    if (c.id_provinsi != null) {
      form.corporate.id_provinsi = c.id_provinsi
      await loadKabupatens(form.corporate.id_provinsi)
      form.corporate.id_kabupaten = c.id_kabupaten ?? null
    }
  }

  // Prefill dari JSON simpanan bila ada. Snapshot ini bisa datang dari dua
  // skema berbeda tergantung siapa yang terakhir menyimpan:
  // - updateByToken (publik, lama): legal_data{corporate,registered,delivery,docs_meta,agreement},
  //   finance_data{invoice,payment,registered,delivery}, logistik_data{logistic,supply}
  // - updateInternal (internal, baru): legal_data{corporate,registered,delivery,documents,agreement},
  //   finance_data{contacts,payment}, logistik_data{logistik,supply}
  try {
    const legal = JSON.parse(data.legal_data || '{}')
    if (legal.corporate)  Object.assign(form.corporate,  legal.corporate)
    if (legal.registered) Object.assign(form.registered, legal.registered)
    if (legal.delivery)   Object.assign(form.delivery,   legal.delivery)
    if (legal.agreement)  Object.assign(form.agreement,  legal.agreement)

    const docsSrc = legal.documents || legal.docs_meta
    if (docsSrc) {
      form.docs.certificate_number = docsSrc.nomor_sertifikat ?? docsSrc.certificate_number ?? form.docs.certificate_number
      form.docs.npwp_number        = docsSrc.nomor_npwp        ?? docsSrc.npwp_number        ?? form.docs.npwp_number
      form.docs.other_doc          = docsSrc.dokumen_lainnya   ?? docsSrc.other_doc           ?? form.docs.other_doc
    }
    if (legal.corporate) {
      form.docs.nib_number = legal.corporate.nib_number ?? form.docs.nib_number
      form.docs.nib_file   = legal.corporate.nib_file   ?? form.docs.nib_file
    }
  } catch {}
  try {
    const finance = JSON.parse(data.finance_data || '{}')
    if (finance.payment)    Object.assign(form.payment,    finance.payment)
    if (finance.registered) Object.assign(form.registered, finance.registered)
    if (finance.delivery)   Object.assign(form.delivery,   finance.delivery)

    // skema lama: finance.invoice = { delivery_address, pic }
    if (finance.invoice) Object.assign(form.invoice, finance.invoice)

    // skema baru: finance.contacts = { pic_invoice, invoice_delivery_addr_primary, ... }
    const picInvoice = finance.contacts?.pic_invoice
    if (picInvoice) {
      form.invoice.pic.name      = picInvoice.name      ?? form.invoice.pic.name
      form.invoice.pic.position  = picInvoice.position  ?? form.invoice.pic.position
      form.invoice.pic.telephone = picInvoice.telp      ?? form.invoice.pic.telephone
      form.invoice.pic.mobile    = picInvoice.mobile    ?? form.invoice.pic.mobile
      form.invoice.pic.email     = picInvoice.email     ?? form.invoice.pic.email
    }
    if (finance.contacts?.invoice_delivery_addr_primary) {
      form.invoice.delivery_address = finance.contacts.invoice_delivery_addr_primary
    }
  } catch {}
  try {
    const logistik = JSON.parse(data.logistik_data || '{}')
    const lg = logistik.logistik || logistik.logistic
    if (lg) Object.assign(form.logistic, lg)
    if (logistik.supply) Object.assign(form.supply, logistik.supply)
  } catch {}

  // registered kabupaten jika ada
  await loadRegisteredKabupatens(form.registered.id_provinsi)
}
onMounted(load)

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
const otherFileModel = computed<File | null>({
  get: () => (form.docs.other_file instanceof File ? form.docs.other_file : null),
  set: (value) => { form.docs.other_file = value }
})

async function upload(key: string){
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
  } catch (e:any) {
    error('Gagal', e?.response?.data?.message || 'Gagal upload')
  } finally {
    busy.value = false
  }
}

async function uploadOtherFile(){
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
  } catch (e:any) {
    error('Gagal', e?.response?.data?.message || 'Gagal upload')
  } finally {
    busy.value = false
  }
}

/* ====== nav ====== */
function next(){ if (step.value < 6) step.value++ }
function prev(){ if (step.value > 1) step.value-- }
function goBack() {
  const fallback = { name: 'customer-verifications' };
  if (window.history.length <= 1) {
    router.push(fallback);               // dibuka dari tab baru → langsung ke list
    return;
  }
  router.back();                         // ada history → balik biasa
  // optional safety fallback kalau back gagal
  setTimeout(() => {
    if (router.currentRoute.value.name === route.name) {
      router.push(fallback);
    }
  }, 150);
}

/* ====== save ====== */
async function save(){
  // Agreement wajib
  if (!form.agreement.updated_by?.trim()) {
    warning('Validasi', 'Updated By wajib diisi pada bagian Agreement.')
    return
  }
  if (!form.agreement.agree) {
    warning('Validasi', 'Centang pernyataan kebenaran data pada Agreement.')
    return
  }

  // PIC Invoice wajib diisi sebelum submit
  const pic = form.invoice.pic
  if (!pic.name?.trim() || !pic.telephone?.trim() || !pic.mobile?.trim()) {
    warning('Validasi', 'PIC Invoice (Nama, Telepon, dan Mobile) wajib diisi sebelum submit.')
    return
  }

  try {
    busy.value = true

    const payload:any = {
      legal_data: JSON.stringify({
        corporate: {
          ...form.corporate,
          nib_number: form.docs.nib_number,
          nib_file:   typeof form.docs.nib_file === 'string' ? form.docs.nib_file : null,
        },
        registered: form.registered,
        delivery:   form.delivery,
        docs_meta: {
          certificate_number: form.docs.certificate_number,
          npwp_number:        form.docs.npwp_number,
          other_doc:          form.docs.other_doc,
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
        supply:   form.supply
      }),
      is_reviewed: 0,
      is_evaluated: 0
    }

    await axios.put(`/api/verify/${token}`, payload)
    success('Tersimpan', 'Data verifikasi berhasil disimpan.')
    router.push({ name: 'customer-verifications' })
  } catch (e:any) {
    error('Gagal', e?.response?.data?.message || 'Gagal menyimpan')
  } finally {
    busy.value = false
  }
}
</script>

<style scoped>
.btn { @apply inline-flex items-center px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700; }
</style>
