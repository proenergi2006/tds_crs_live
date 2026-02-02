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
        <!-- Steps header -->
        <div class="flex flex-wrap items-center gap-2 mb-6">
          <div
            v-for="n in 6"
            :key="n"
            class="px-3 py-1 rounded-full text-sm"
            :class="step === n ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700'"
          >
            Step {{ n }}/6
          </div>
  
          <div class="ml-auto flex gap-2">
            <button class="px-3 py-2 rounded border" @click="goBack">Kembali</button>
            <button v-if="step>1" class="px-3 py-2 rounded border" :disabled="busy" @click="prev">Prev</button>
            <button v-if="step<6" class="px-3 py-2 rounded border bg-emerald-600 text-white hover:bg-emerald-700"
                    :disabled="busy" @click="next">
              Next
            </button>
            <button v-else class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                    :disabled="busy" @click="save">
              {{ busy ? 'Menyimpan…' : 'Simpan' }}
            </button>
          </div>
        </div>
  
        <!-- =========================
             STEP 1 – CORPORATE DETAILS
             ========================= -->
        <div v-show="step===1" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            1. CORPORATE DETAILS
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-5">
            <div>
              <label class="block text-sm font-medium mb-1">FULL REGISTERED COMPANY NAME *</label>
              <input v-model="form.corporate.nama" type="text" class="form" placeholder="Nama perusahaan"/>
            </div>
  
            <div>
              <label class="block text-sm font-medium mb-1">HOLDING (Jika ada)</label>
              <input v-model="form.corporate.holding" type="text" class="form"/>
            </div>
  
            <div>
              <label class="block text-sm font-medium mb-1">Cetakan Product</label>
              <input v-model="form.corporate.print_product" type="text" class="form"/>
            </div>
  
            <!-- Address of Head Office -->
            <div class="border-t pt-4">
              <div class="font-semibold mb-2">ADDRESS OF HEAD OFFICE</div>
  
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm mb-1">Email *</label>
                  <input v-model="form.corporate.email" type="email" class="form"/>
                </div>
                <div>
                  <label class="block text-sm mb-1">Website</label>
                  <input v-model="form.corporate.website" type="text" class="form"/>
                </div>
  
                <div class="md:col-span-2">
                  <label class="block text-sm mb-1">Address *</label>
                  <textarea v-model="form.corporate.alamat" rows="3" class="form"></textarea>
                </div>
  
                <!-- Provinsi -->
                <div>
                  <label class="block text-sm mb-1">Province / Provinsi *</label>
  
                  <!-- select jika master ada, kalau tidak fallback ke input -->
                  <select v-if="provinsiOptions.length"
                          v-model="form.corporate.id_provinsi"
                          class="form">
                    <option :value="null" disabled>Pilih provinsi</option>
                    <option v-for="p in provinsiOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                  <input v-else v-model="form.corporate.provinsi_text" type="text" class="form" placeholder="Provinsi"/>
                </div>
  
                <!-- Kabupaten -->
                <div>
                  <label class="block text-sm mb-1">City / Kota (Kabupaten) *</label>
  
                  <select v-if="kabupatenOptions.length"
                          v-model="form.corporate.id_kabupaten"
                          class="form"
                          :disabled="!form.corporate.id_provinsi">
                    <option :value="null" disabled>Pilih kabupaten/kota</option>
                    <option v-for="k in kabupatenOptions" :key="k.id" :value="k.id">{{ k.name }}</option>
                  </select>
                  <input v-else v-model="form.corporate.kabupaten_text" type="text" class="form" placeholder="Kota/Kabupaten"/>
                </div>
  
                <div>
                  <label class="block text-sm mb-1">Districts / Kecamatan</label>
                  <input v-model="form.corporate.kecamatan" type="text" class="form"/>
                </div>
                <div>
                  <label class="block text-sm mb-1">Sub-Districts / Kelurahan</label>
                  <input v-model="form.corporate.kelurahan" type="text" class="form"/>
                </div>
  
                <div>
                  <label class="block text-sm mb-1">Postal Code</label>
                  <input v-model="form.corporate.postal_code" type="text" class="form"/>
                </div>
  
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm mb-1">Telephone *</label>
                    <input v-model="form.corporate.telepon" type="text" class="form"/>
                  </div>
                  <div>
                    <label class="block text-sm mb-1">Fax</label>
                    <input v-model="form.corporate.fax" type="text" class="form"/>
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Registered Street Address -->
            <div class="border-t pt-4">
              <div class="font-semibold mb-2">REGISTERED STREET ADDRESS (NPWP Address)</div>
  
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm mb-1">Email *</label>
                  <input v-model="form.registered.email" type="email" class="form"/>
                </div>
  
                <div class="md:col-span-2">
                  <label class="block text-sm mb-1">Address *</label>
                  <textarea v-model="form.registered.alamat" rows="3" class="form"></textarea>
                </div>
  
                <div>
                  <label class="block text-sm mb-1">Province / Provinsi *</label>
                  <select v-if="provinsiOptions.length"
                          v-model="form.registered.id_provinsi"
                          class="form">
                    <option :value="null" disabled>Pilih provinsi</option>
                    <option v-for="p in provinsiOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                  <input v-else v-model="form.registered.provinsi_text" type="text" class="form" placeholder="Provinsi"/>
                </div>
  
                <div>
                  <label class="block text-sm mb-1">City / Kabupaten *</label>
                  <select v-if="registeredKabupatenOptions.length"
                          v-model="form.registered.id_kabupaten"
                          class="form"
                          :disabled="!form.registered.id_provinsi">
                    <option :value="null" disabled>Pilih kabupaten/kota</option>
                    <option v-for="k in registeredKabupatenOptions" :key="k.id" :value="k.id">{{ k.name }}</option>
                  </select>
                  <input v-else v-model="form.registered.kabupaten_text" type="text" class="form" placeholder="Kota/Kabupaten"/>
                </div>
              </div>
            </div>
  
            <!-- Product Delivery Address -->
            <div class="border-t pt-4">
              <div class="font-semibold mb-2">PRODUCT DELIVERY FULL ADDRESS OR SITE ADDRESS</div>
  
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm mb-1">Alamat 1</label>
                  <textarea v-model="form.delivery.alamat1" rows="3" class="form"></textarea>
                </div>
                <div>
                  <label class="block text-sm mb-1">Alamat 2</label>
                  <textarea v-model="form.delivery.alamat2" rows="3" class="form"></textarea>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm mb-1">Alamat 3</label>
                  <textarea v-model="form.delivery.alamat3" rows="3" class="form"></textarea>
                </div>
              </div>
            </div>
  
            <!-- Type of Business + Ownership -->
            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-semibold mb-2">TYPE OF BUSINESS</div>
                <div class="space-y-2">
                  <div v-for="opt in typeBusiness" :key="opt" class="flex items-center gap-2">
                    <input type="radio" :value="opt" v-model="form.corporate.tipe_bisnis"/>
                    <span>{{ opt }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.corporate.tipe_bisnis"/>
                    <span>Other,</span>
                  </div>
                  <input v-model="form.corporate.tipe_bisnis_lain" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
  
              <div>
                <div class="font-semibold mb-2">OWNERSHIP</div>
                <div class="space-y-2">
                  <div v-for="opt in ownershipOpts" :key="opt" class="flex items-center gap-2">
                    <input type="radio" :value="opt" v-model="form.corporate.ownership"/>
                    <span>{{ opt }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.corporate.ownership"/>
                    <span>Other,</span>
                  </div>
                  <input v-model="form.corporate.ownership_lain" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- =========================
             STEP 2 – DOCUMENTATION
             ========================= -->
        <div v-show="step===2" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            2. DOCUMENTATION
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-5">
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Akta -->
              <div>
                <label class="block text-sm font-medium mb-1">Certificate Number (Akta Pendirian)</label>
                <input v-model="form.docs.certificate_number" type="text" class="form"/>
                <div class="mt-2 flex items-center gap-2">
                  <input type="file" @change="onFile($event,'akta_file')" />
                  <button class="btn" @click="upload('akta_file')">Upload</button>
                </div>
              </div>
  
              <!-- NPWP -->
              <div>
                <label class="block text-sm font-medium mb-1">NPWP Number *</label>
                <input v-model="form.docs.npwp_number" type="text" class="form"/>
                <div class="mt-2 flex items-center gap-2">
                  <input type="file" @change="onFile($event,'npwp_file')" />
                  <button class="btn" @click="upload('npwp_file')">Upload</button>
                </div>
              </div>
  
              <!-- SIUP -->
              <div>
                <label class="block text-sm font-medium mb-1">SIUP Number *</label>
                <input v-model="form.docs.siup_number" type="text" class="form"/>
                <div class="mt-2 flex items-center gap-2">
                  <input type="file" @change="onFile($event,'siup_file')" />
                  <button class="btn" @click="upload('siup_file')">Upload</button>
                </div>
              </div>
  
              <!-- TDP -->
              <div>
                <label class="block text-sm font-medium mb-1">TDP Number *</label>
                <input v-model="form.docs.tdp_number" type="text" class="form"/>
                <div class="mt-2 flex items-center gap-2">
                  <input type="file" @change="onFile($event,'tdp_file')" />
                  <button class="btn" @click="upload('tdp_file')">Upload</button>
                </div>
              </div>
  
              <!-- Dokumen lain -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Dokumen Lainnya</label>
                <input v-model="form.docs.other_doc" type="text" class="form"/>
                <div class="mt-2 flex items-center gap-2">
                  <input type="file" @change="onFile($event,'other_file')" />
                  <button class="btn" @click="upload('other_file')">Upload</button>
                </div>
              </div>
            </div>
  
            <p class="text-xs text-slate-500">
              * Max size sesuai kebijakan (mis. 2–10MB). Ekstensi yang diizinkan: jpg, jpeg, png, pdf, zip, rar.
            </p>
          </div>
        </div>
  
        <!-- =========================
             STEP 3 – PAYMENT TERM & BANKING DETAIL
             ========================= -->
        <div v-show="step===3" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            3. PAYMENT TERM & BANKING DETAIL
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <!-- Pricing Method Calculation -->
              <div>
                <div class="font-semibold mb-2">PRICING METHOD CALCULATION *</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Discount Pricelist" v-model="form.payment.pricing_method">
                    <span>Discount Pricelist</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Formula MOPS" v-model="form.payment.pricing_method">
                    <span>Formula MOPS</span>
                  </label>
                </div>
              </div>
  
              <!-- Payment Metode -->
              <div>
                <div class="font-semibold mb-2">PAYMENT METODE *</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2" v-for="m in payMethods" :key="m">
                    <input type="radio" :value="m" v-model="form.payment.payment_method">
                    <span>{{ m }}</span>
                  </label>
                  <div class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.payment.payment_method">
                    <span>Other,</span>
                  </div>
                  <input v-model="form.payment.payment_method_other" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
            </div>
  
            <!-- Payment Term -->
            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm mb-1">Payment Type</label>
                <input v-model="form.payment.payment_type" type="text" class="form" placeholder="Pilih salah satu"/>
              </div>
              <div>
                <label class="block text-sm mb-1">Currency / Mata Uang</label>
                <input v-model="form.payment.currency" type="text" class="form"/>
              </div>
              <div>
                <label class="block text-sm mb-1">Bank Name / Nama Bank</label>
                <input v-model="form.payment.bank_name" type="text" class="form"/>
              </div>
              <div>
                <label class="block text-sm mb-1">Account Number / Nomor Rekening</label>
                <input v-model="form.payment.account_number" type="text" class="form"/>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm mb-1">Bank Address / Alamat Bank</label>
                <textarea v-model="form.payment.bank_address" rows="3" class="form"></textarea>
              </div>
  
              <div class="md:col-span-2">
                <div class="font-semibold mb-2">Have Credit Facility or Bank Loan?</div>
                <div class="flex items-center gap-5">
                  <label class="flex items-center gap-2">
                    <input type="radio" :value="true" v-model="form.payment.has_credit"/>
                    <span>Yes</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" :value="false" v-model="form.payment.has_credit"/>
                    <span>No</span>
                  </label>
                </div>
              </div>
  
              <div class="md:col-span-2">
                <label class="block text-sm mb-1">Nama Penyedia Kredit atau Pinjaman</label>
                <input v-model="form.payment.creditor_name" type="text" class="form" placeholder="Specify"/>
              </div>
            </div>
  
            <div class="border-t pt-4 grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-semibold mb-2">CASHIER AND PAYMENT SCHEDULE *</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Every Day" v-model="form.payment.schedule">
                    <span>Every Day</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.payment.schedule">
                    <span>Other,</span>
                  </label>
                  <input v-model="form.payment.schedule_other" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
  
              <div>
                <div class="font-semibold mb-2">INVOICES</div>
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" v-model="form.payment.invoice_tax"/>
                  <span>Tax Invoice (Faktur Pajak)</span>
                </label>
              </div>
  
              <div class="md:col-span-2">
                <label class="block text-sm mb-1">KETERANGAN (Jika Ada)</label>
                <textarea v-model="form.payment.note" rows="3" class="form"></textarea>
              </div>
            </div>
          </div>
        </div>
  
        <!-- =========================
             STEP 4 – SUPPLY SCHEME
             ========================= -->
        <div v-show="step===4" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            4. SUPPLY SCHEME
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm mb-1">SUPPLY SCHEME DETAILS *</label>
                <input v-model="form.supply.scheme_details" class="form" placeholder="Pilih salah satu"/>
              </div>
              <div>
                <label class="block text-sm mb-1">Print Product *</label>
                <input v-model="form.supply.print_product" class="form"/>
              </div>
              <div>
                <label class="block text-sm mb-1">SPECIFY PRODUCT *</label>
                <input v-model="form.supply.specify_product" class="form" placeholder="Pilih salah satu"/>
              </div>
              <div>
                <label class="block text-sm mb-1">VOLUME PER MONTH *</label>
                <input v-model="form.supply.volume_per_month" class="form" type="number" min="0"/>
              </div>
            </div>
  
            <div class="grid md:grid-cols-2 gap-6">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm mb-1">OPERATONAL HOUR – FROM</label>
                  <input v-model="form.supply.operational_from" type="time" class="form"/>
                </div>
                <div>
                  <label class="block text-sm mb-1">TO</label>
                  <input v-model="form.supply.operational_to" type="time" class="form"/>
                </div>
              </div>
              <div>
                <label class="block text-sm mb-1">INCO TERMS *</label>
                <input v-model="form.supply.inco_terms" class="form" placeholder="Pilih salah satu"/>
              </div>
            </div>
          </div>
        </div>
  
        <!-- =========================
             STEP 5 – LOGISTIC
             ========================= -->
        <div v-show="step===5" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            5. LOGISTIC
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-6">
            <div>
              <div class="font-semibold mb-2">DESCRIPTION AND CAPACITY OF FACILITIES BUSINESS LOCATION</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm mb-1">Area (Luas Lokasi)</label>
                  <textarea v-model="form.logistic.area" rows="3" class="form"></textarea>
                </div>
                <div>
                  <label class="block text-sm mb-1">Security Environment / Business Area</label>
                  <textarea v-model="form.logistic.security_env" rows="3" class="form"></textarea>
                </div>
              </div>
            </div>
  
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-semibold mb-2">Conditions Around Locations</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Industri" v-model="form.logistic.env" />
                    <span>Industri</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Pemukiman" v-model="form.logistic.env" />
                    <span>Pemukiman</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.env" />
                    <span>Other,</span>
                  </label>
                  <input v-model="form.logistic.env_other" type="text" class="form" placeholder="Specify"/>
                </div>
  
                <div class="mt-4">
                  <label class="block text-sm mb-1">Description Of Condition</label>
                  <textarea v-model="form.logistic.condition_desc" rows="3" class="form"></textarea>
                </div>
              </div>
  
              <div>
                <div class="font-semibold mb-2">Storage Facility</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Indoor" v-model="form.logistic.storage"/>
                    <span>Indoor</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Outdoor" v-model="form.logistic.storage"/>
                    <span>Outdoor</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.storage"/>
                    <span>Other,</span>
                  </label>
                  <input v-model="form.logistic.storage_other" type="text" class="form" placeholder="Specify"/>
                </div>
  
                <div class="mt-4">
                  <label class="block text-sm mb-1">Description Of Storage Facility</label>
                  <textarea v-model="form.logistic.storage_desc" rows="3" class="form"></textarea>
                </div>
              </div>
            </div>
  
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-semibold mb-2">LOGISTICS DETAIL – Operating Hours</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="08.00 - 17.00" v-model="form.logistic.operating_hours">
                    <span>08.00 - 17.00</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="24 Hours" v-model="form.logistic.operating_hours">
                    <span>24 Hours</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.operating_hours">
                    <span>Other,</span>
                  </label>
                  <input v-model="form.logistic.operating_hours_other" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
  
              <div>
                <div class="font-semibold mb-2">Quality Checking</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.logistic.quality_density">
                    <span>DENSITY</span>
                  </label>
                  <div class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.logistic.quality_other_enabled">
                    <span>Other,</span>
                  </div>
                  <input v-model="form.logistic.quality_other" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
            </div>
  
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <div class="font-semibold mb-2">Volume Measurement</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2">
                    <input type="radio" value="PRO ENERGY'S TANK LORRY" v-model="form.logistic.volume_measurement">
                    <span>PRO ENERGY'S TANK LORRY</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Flowmeter" v-model="form.logistic.volume_measurement">
                    <span>Flowmeter</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input type="radio" value="Other" v-model="form.logistic.volume_measurement">
                    <span>Other,</span>
                  </label>
                  <input v-model="form.logistic.volume_measurement_other" type="text" class="form" placeholder="Specify"/>
                </div>
              </div>
  
              <div>
                <div class="font-semibold mb-2">Max. Truck Capacity</div>
                <div class="space-y-2">
                  <label class="flex items-center gap-2" v-for="cap in ['5 KL','8 KL','10 KL','16 KL']" :key="cap">
                    <input type="radio" :value="cap" v-model="form.logistic.max_truck_capacity">
                    <span>{{ cap }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- =========================
             STEP 6 – AGREEMENT & CAPTCHA
             ========================= -->
        <div v-show="step===6" class="mb-8">
          <div class="bg-emerald-700 text-white px-4 py-3 rounded-t font-semibold">
            6. AGREEMENT & CAPTCHA
          </div>
          <div class="bg-white p-4 rounded-b shadow space-y-6">
            <!-- Agreement -->
            <div>
              <div class="text-lg font-semibold mb-2">AGREEMENT</div>
              <div class="grid md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-sm mb-1">Updated By</label>
                  <input v-model="form.agreement.updated_by" type="text" class="form" placeholder="Nama pengisi form"/>
                </div>
  
                <div class="md:col-span-2">
                  <label class="inline-flex items-center gap-3">
                    <input type="checkbox" v-model="form.agreement.agree"/>
                    <span class="text-[15px]">
                      I declare that the above data is true (Dengan ini saya menyatakan bahwa data diatas benar adanya)
                    </span>
                  </label>
                </div>
              </div>
            </div>
  
            <!-- Captcha -->
            <div>
              <div class="text-lg font-semibold mb-2">Captcha *</div>
  
              <div v-if="!isPublic" class="text-slate-600 text-sm">
                CAPTCHA tidak diperlukan untuk akses internal (login). Klik <b>Simpan</b> untuk menyelesaikan.
              </div>
  
              <div v-else class="space-y-3">
                <div class="flex items-center gap-4">
                  <img v-if="captcha.image" :src="captcha.image" alt="CAPTCHA" class="border rounded h-12"/>
                  <button type="button" class="btn" @click="refreshCaptcha">Reload</button>
                </div>
                <input v-model="captcha.text" type="text" class="form" placeholder="Masukkan kode"/>
                <div v-if="captchaError" class="text-rose-600 text-sm">{{ captchaError }}</div>
                <div class="text-slate-500 text-sm">Ketik ulang kode pada gambar.</div>
              </div>
            </div>
          </div>
        </div>
  
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { reactive, ref, onMounted, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  const route = useRoute()
  const router = useRouter()
  
  // Bisa dibuka via token (public) atau by id (internal)
  const token   = (route.params.token as string) || undefined
  const idParam = route.params.id ? Number(route.params.id) : undefined
  const isPublic = !!token
  
  const busy = ref(false)
  const step = ref(1)
  const verificationId = ref<number|null>(null)
  
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
    docs: { certificate_number:'', npwp_number:'', siup_number:'', tdp_number:'', other_doc:'',
            akta_file:null, npwp_file:null, siup_file:null, tdp_file:null, other_file:null },
    payment: {
      pricing_method:'', payment_method:'', payment_method_other:'',
      payment_type:'', currency:'', bank_name:'', account_number:'', bank_address:'',
      has_credit:false, creditor_name:'',
      schedule:'', schedule_other:'', invoice_tax:false, note:''
    },
    supply: { scheme_details:'', print_product:'', specify_product:'', volume_per_month:null,
              operational_from:'', operational_to:'', inco_terms:'' },
    logistic: {
      area:'', security_env:'',
      env:'', env_other:'', condition_desc:'',
      storage:'', storage_other:'', storage_desc:'',
      operating_hours:'', operating_hours_other:'',
      quality_density:false, quality_other_enabled:false, quality_other:'',
      volume_measurement:'', volume_measurement_other:'',
      max_truck_capacity:''
    },
    agreement: { updated_by:'', agree:false }
  })
  
  /* ====== captcha state ====== */
  const captcha = reactive<{key:string|null; image:string; text:string}>({ key:null, image:'', text:'' })
  const captchaError = ref('')
  
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
  
  /* ====== captcha load ====== */
  async function loadCaptcha(){
    if (!isPublic) return
    captchaError.value = ''
    captcha.text = ''
    const { data } = await axios.get('/api/captcha')
    captcha.key   = data.key
    captcha.image = data.image
  }
  function refreshCaptcha(){ loadCaptcha() }
  watch(step, s => { if (s === 6) loadCaptcha() })
  
  /* ====== load data verifikasi ====== */
  async function load(){
    await loadProvinsis()
  
    const url = isPublic ? `/api/verify/${token}` : `/api/customer-verifications/${idParam}`
    const { data } = await axios.get(url)
    verificationId.value = data.id_verification
  
    // Prefill dari relasi customer (pertama kali masuk)
    if (data.customer) {
      const c = data.customer
      form.corporate.nama        = c.nama_perusahaan || ''
      form.corporate.email       = c.email || ''
      form.corporate.alamat      = c.alamat_perusahaan || ''
      form.corporate.telepon     = c.telepon || ''
      form.corporate.fax         = c.fax || ''
      form.corporate.postal_code = c.postal_code || ''
  
      form.corporate.id_provinsi  = c.id_provinsi ?? null
      await loadKabupatens(form.corporate.id_provinsi)
      form.corporate.id_kabupaten = c.id_kabupaten ?? null
    }
  
    // Prefill dari JSON simpanan bila ada
    try {
      const legal = JSON.parse(data.legal_data || '{}')
      if (legal.corporate)  Object.assign(form.corporate,  legal.corporate)
      if (legal.registered) Object.assign(form.registered, legal.registered)
      if (legal.delivery)   Object.assign(form.delivery,   legal.delivery)
      if (legal.agreement)  Object.assign(form.agreement,  legal.agreement)
    } catch {}
    try {
      const finance = JSON.parse(data.finance_data || '{}')
      if (finance.invoice)    Object.assign(form.invoice,    finance.invoice)
      if (finance.payment)    Object.assign(form.payment,    finance.payment)
      if (finance.registered) Object.assign(form.registered, finance.registered)
      if (finance.delivery)   Object.assign(form.delivery,   finance.delivery)
    } catch {}
    try {
      const logistik = JSON.parse(data.logistik_data || '{}')
      if (logistik.logistic) Object.assign(form.logistic, logistik.logistic)
      if (logistik.supply)   Object.assign(form.supply,   logistik.supply)
    } catch {}
  
    // registered kabupaten jika ada
    await loadRegisteredKabupatens(form.registered.id_provinsi)
  
    if (isPublic && step.value === 6) await loadCaptcha()
  }
  onMounted(load)
  
  /* ====== file upload (opsional) ====== */
  function onFile(e: Event, key: string){
    const input = e.target as HTMLInputElement
    form.docs[key] = input.files?.[0] || null
  }
  async function upload(key: string){
    if (!form.docs[key]) return Swal.fire('Info', 'Pilih file dulu', 'info')
    const fd = new FormData()
    fd.append('file', form.docs[key])
    fd.append('field', key)
    try {
      busy.value = true
      await axios.post(`/api/customer-verifications/${verificationId.value}/upload`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      Swal.fire('OK', 'File diupload', 'success')
    } catch (e:any) {
      Swal.fire('Error', e?.response?.data?.message || 'Gagal upload', 'error')
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
    // Agreement wajib saat public
    if (isPublic) {
      if (!form.agreement.updated_by?.trim()) {
        return Swal.fire('Validasi', 'Updated By wajib diisi pada bagian Agreement.', 'warning')
      }
      if (!form.agreement.agree) {
        return Swal.fire('Validasi', 'Centang pernyataan kebenaran data pada Agreement.', 'warning')
      }
    }
  
    const saveUrl = isPublic ? `/api/verify/${token}` : `/api/customer-verifications/${verificationId.value}`
  
    try {
      busy.value = true
      captchaError.value = ''
  
      const payload:any = {
        legal_data: JSON.stringify({
          corporate:  form.corporate,
          registered: form.registered,
          delivery:   form.delivery,
          docs_meta: {
            certificate_number: form.docs.certificate_number,
            npwp_number:        form.docs.npwp_number,
            siup_number:        form.docs.siup_number,
            tdp_number:         form.docs.tdp_number,
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
  
      if (isPublic) {
        payload.captcha_key  = captcha.key
        payload.captcha_text = captcha.text
      }
  
      await axios.put(saveUrl, payload)
      Swal.fire({ icon:'success', title:'Tersimpan', text:'Data verifikasi berhasil disimpan.' })
      router.push({ name: 'customer-verifications' })
    } catch (e:any) {
      if (e?.response?.status === 422 && isPublic) {
        captchaError.value = e?.response?.data?.message || 'Captcha tidak valid'
        refreshCaptcha()
      } else {
        Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan', 'error')
      }
    } finally {
      busy.value = false
    }
  }
  </script>
  
  <style scoped>
  .form { @apply w-full border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-emerald-200; }
  .btn  { @apply inline-flex items-center px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700; }
  </style>
  