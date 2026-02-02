<template>
    <div class="p-6 intro-y">
      <!-- Header -->
      <div class="flex items-center">
        <h2 class="text-lg font-medium">{{ isEdit ? 'Edit' : 'Create' }} Customer LCR</h2>
  
        <RouterLink :to="{ name: 'lcr-list' }" class="ml-auto">
          <Button variant="outline-secondary">Kembali</Button>
        </RouterLink>
  
        <Button class="ml-2" variant="primary" :disabled="loading" @click="submit">
          <Lucide icon="Save" class="w-4 h-4 mr-2" /> Save
        </Button>
  
        <!-- tombol modal media muncul kalau sudah di-approve -->
        <Button
          v-if="form.flag_approval === 1"
          variant="outline-primary"
          class="ml-3"
          @click="openMediaModal()"
        >
          Kelola Gambar…
        </Button>
      </div>
  
      <!-- 1. General Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">1. General Information</div>
        <div class="p-5 grid grid-cols-12 gap-5">
          <!-- Customer -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Nama Perusahaan *</FormLabel>
            <SearchableRemoteSelect
              v-model="form.id_customer"
              :fetcher="searchCustomers"
              :getById="getCustomerById"
              placeholder="Pilih customer…"
              :invalid="!!firstError('id_customer')"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('id_customer')">
              {{ firstError('id_customer') }}
            </div>
          </div>
  
          <!-- Cabang (auto isi id_wilayah) -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Wilayah</FormLabel>
            <SearchableRemoteSelect
              v-model="form.id_wilayah"
              :fetcher="searchCabangs"
              :getById="getCabangById"
              placeholder="Pilih cabang…"
              :invalid="!!firstError('id_cabang')"
              @selected="(o:any) => { if (o?.raw?.id_wilayah) form.id_wilayah = o.raw.id_wilayah }"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('id_cabang')">
              {{ firstError('id_cabang') }}
            </div>
          </div>
  
          <!-- Wilayah OA -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Wilayah OA *</FormLabel>
            <SearchableRemoteSelect
              v-model="form.id_wil_oa"
              :fetcher="searchWilayahOa"
              :getById="getWilayahOaById"
              placeholder="Pilih wilayah OA…"
              :invalid="!!firstError('id_wil_oa')"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('id_wil_oa')">
              {{ firstError('id_wil_oa') }}
            </div>
          </div>
  
          <!-- Alamat -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Alamat Lokasi *</FormLabel>
            <FormTextarea
              v-model="form.alamat_survey"
              rows="2"
              :class="firstError('alamat_survey') ? 'border-danger ring-1 ring-danger/40' : ''"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('alamat_survey')">
              {{ firstError('alamat_survey') }}
            </div>
          </div>
  
          <!-- Tanggal Survey -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Tanggal Survey *</FormLabel>
            <FormInput
              v-model="form.tgl_survey"
              type="date"
              :class="firstError('tgl_survey') ? 'border-danger ring-1 ring-danger/40' : ''"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('tgl_survey')">
              {{ firstError('tgl_survey') }}
            </div>
          </div>
  
          <!-- Review -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Review</FormLabel>
            <FormTextarea v-model="form.review" rows="3" />
          </div>
  
          <!-- Jenis Usaha -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Jenis Usaha *</FormLabel>
            <FormSelect
              v-model="form.jenis_usaha"
              :class="firstError('jenis_usaha') ? 'border-danger ring-1 ring-danger/40' : ''"
            >
              <option value="">- Pilihan -</option>
              <option v-for="o in jenisUsahaOptions" :key="o" :value="o">{{ o }}</option>
            </FormSelect>
            <div class="text-xs text-red-600 mt-1" v-if="firstError('jenis_usaha')">
              {{ firstError('jenis_usaha') }}
            </div>
          </div>
  
          <!-- Website -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Website</FormLabel>
            <FormInput
              v-model="form.website"
              placeholder="https://..."
              :class="firstError('website') ? 'border-danger ring-1 ring-danger/40' : ''"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('website')">
              {{ firstError('website') }}
            </div>
          </div>
  
          <!-- Surveyor -->
          <div class="col-span-12">
            <div class="font-semibold mb-2">Surveyor</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase text-left">No</th>
                    <th class="px-3 py-2 text-xs uppercase text-left">Nama Surveyor</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(s, i) in surveyorRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2"><FormInput v-model="surveyorRows[i]" /></td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addSurveyor"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeSurveyor(i)" :disabled="surveyorRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
  
          <!-- Hasil -->
          <div class="col-span-12 md:col-span-6">
            <div class="font-semibold mb-2">Hasil</div>
            <div class="space-y-2">
              <div class="flex items-center" v-for="(h,i) in hasilRows" :key="i">
                <FormInput v-model="hasilRows[i]" class="flex-1" />
                <Button size="sm" class="ml-2" @click="addHasil"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                <Button size="sm" variant="danger" class="ml-2" @click="removeHasil(i)" :disabled="hasilRows.length===1">
                  <Lucide icon="X" class="w-4 h-4"/>
                </Button>
              </div>
            </div>
          </div>
  
          <!-- Produk / Volume Bulan -->
          <div class="col-span-12 md:col-span-6">
            <div class="font-semibold mb-2">Produk & Volume/Bulan</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Produk</th>
                    <th class="px-3 py-2 text-xs uppercase">Volume/Bulan</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(p,i) in produkVolRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2"><FormInput v-model="p.produk" /></td>
                    <td class="px-3 py-2"><FormInput v-model="p.volbul" placeholder="5000"/></td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addProdukVol"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeProdukVol(i)" :disabled="produkVolRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
  
          <!-- PICustomer -->
          <div class="col-span-12">
            <div class="font-semibold mb-2">Penanggung Jawab</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Nama</th>
                    <th class="px-3 py-2 text-xs uppercase">Posisi</th>
                    <th class="px-3 py-2 text-xs uppercase">Telepon</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(p,i) in picRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2"><FormInput v-model="p.nama"/></td>
                    <td class="px-3 py-2"><FormInput v-model="p.posisi"/></td>
                    <td class="px-3 py-2"><FormInput v-model="p.telepon"/></td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addPic"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removePic(i)" :disabled="picRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
  
          <!-- Komitmen / Toleransi -->
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Komitmen Penerimaan</FormLabel>
            <FormSelect v-model="form.logistik_result">
              <option :value="1">Ok</option>
              <option :value="0">Tidak</option>
              <option :value="2">Perlu Review</option>
            </FormSelect>
          </div>
          <div class="col-span-12 md:col-span-6">
            <FormLabel>Toleransi</FormLabel>
            <FormInput v-model="form.toleransi" placeholder="0.5 %" />
          </div>
  
          <!-- Kompetitor -->
          <div class="col-span-12">
            <div class="font-semibold mb-2">Kompetitor</div>
            <div class="space-y-2">
              <div class="flex items-center" v-for="(k,i) in kompetitorRows" :key="i">
                <FormInput v-model="kompetitorRows[i]" class="flex-1" />
                <Button size="sm" class="ml-2" @click="addKompetitor"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                <Button size="sm" variant="danger" class="ml-2" @click="removeKompetitor(i)" :disabled="kompetitorRows.length===1">
                  <Lucide icon="X" class="w-4 h-4"/>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- 2. Location Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">2. Location Information</div>
        <div class="p-5 grid grid-cols-12 gap-5">
          <div class="col-span-12 md:col-span-3">
            <FormLabel>Latitude *</FormLabel>
            <FormInput
              v-model="form.latitude_lokasi"
              placeholder="-6.9"
              :class="firstError('latitude_lokasi') ? 'border-danger ring-1 ring-danger/40' : ''"
            />
            <div class="text-xs text-red-600 mt-1" v-if="firstError('latitude_lokasi')">
              {{ firstError('latitude_lokasi') }}
            </div>
          </div>
          <div class="col-span-12 md:col-span-3">
            <FormLabel>Longitude *</FormLabel>
            <div class="flex">
              <FormInput
                v-model="form.longitude_lokasi"
                placeholder="112.45"
                class="flex-1"
                :class="firstError('longitude_lokasi') ? 'border-danger ring-1 ring-danger/40' : ''"
              />
              <Button class="ml-2" @click="buildMapLink"><Lucide icon="Search" class="w-4 h-4" /></Button>
            </div>
            <div class="text-xs text-red-600 mt-1" v-if="firstError('longitude_lokasi')">
              {{ firstError('longitude_lokasi') }}
            </div>
          </div>
          <div class="col-span-12 md:col-span-3">
            <FormLabel>Jarak dari Jetty</FormLabel>
            <FormInput v-model="form.jarak_depot" placeholder="39,1" />
          </div>
          <div class="col-span-12">
            <FormLabel>Link Google Maps</FormLabel>
            <FormInput v-model="form.link_google_maps" placeholder="https://maps.google.com/..." />
          </div>
  
          <div class="col-span-12 md:col-span-8">
            <div class="text-slate-500 mb-2">Preview Map</div>
            <div class="border rounded overflow-hidden h-72 bg-slate-50">
              <iframe v-if="mapUrl" :src="mapUrl" class="w-full h-full" loading="lazy"></iframe>
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                Isi Latitude & Longitude untuk preview
              </div>
            </div>
          </div>
  
          <div class="col-span-12 md:col-span-4 grid grid-cols-12 gap-4">
            <div class="col-span-12">
              <FormLabel>Rute</FormLabel>
              <FormTextarea v-model="form.rute_lokasi" rows="5" />
            </div>
            <div class="col-span-12">
              <FormLabel>Catatan</FormLabel>
              <FormTextarea v-model="form.note_lokasi" rows="5" />
            </div>
          </div>
  
          <div class="col-span-12 md:col-span-4">
            <FormLabel>Max Kapasitas Truk</FormLabel>
            <FormSelect v-model="form.max_truk">
              <option value="">Pilih salah satu</option>
              <option v-for="o in maxTrukOptions" :key="o" :value="o">{{ o }}</option>
            </FormSelect>
          </div>
          <div class="col-span-12 md:col-span-4">
            <FormLabel>Min Kapasitas Truck</FormLabel>
            <FormInput v-model="form.min_vol_kirim" placeholder="5 (m³)" />
          </div>
          <div class="col-span-12">
            <FormLabel>Penjelasan Proses Bongkaran</FormLabel>
            <FormTextarea v-model="form.penjelasan_bongkar" rows="3" />
          </div>
        </div>
      </div>
  
      <!-- 3. Unloading Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">3. Unloading Information</div>
  
        <div class="p-5 space-y-8">
          <!-- Truck (Tangki) -->
          <div>
            <div class="font-semibold mb-3">Informasi Pembongkaran Media Truck</div>
  
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Tipe</th>
                    <th class="px-3 py-2 text-xs uppercase">Kapasitas</th>
                    <th class="px-3 py-2 text-xs uppercase">Jumlah</th>
                    <th class="px-3 py-2 text-xs uppercase">Produk</th>
                    <th class="px-3 py-2 text-xs uppercase">Inlet Pipa</th>
                    <th class="px-3 py-2 text-xs uppercase">Ukuran</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(t,i) in tangkiRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="t.tipe">
                        <option value="">- Pilihan -</option>
                        <option v-for="o in tangkiTypeOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2"><FormInput v-model="t.kapasitas" placeholder="7KL" /></td>
                    <td class="px-3 py-2"><FormInput v-model="t.jumlah" placeholder="1" /></td>
                    <td class="px-3 py-2"><FormInput v-model="t.produk" placeholder="HSD" /></td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="t.inlet">
                        <option value="">- Pilihan -</option>
                        <option v-for="o in inletOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="t.ukuran">
                        <option value="">- Pilihan -</option>
                        <option v-for="o in ukuranOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addTangki"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeTangki(i)" :disabled="tangkiRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Pendukung -->
            <div class="mt-6 font-semibold mb-2">Pendukung</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Pompa</th>
                    <th class="px-3 py-2 text-xs uppercase">Laju Aliran</th>
                    <th class="px-3 py-2 text-xs uppercase">P. Selang</th>
                    <th class="px-3 py-2 text-xs uppercase">Valve</th>
                    <th class="px-3 py-2 text-xs uppercase">Grounding</th>
                    <th class="px-3 py-2 text-xs uppercase">Sinyal HP</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(p,i) in pendukungRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="p.pompa">
                        <option value="">- Pilihan -</option>
                        <option v-for="o in pompaOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2"><FormInput v-model="p.aliran" placeholder="300 LPM" /></td>
                    <td class="px-3 py-2"><FormInput v-model="p.selang" placeholder="15 M" /></td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="p.valve">
                        <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="p.ground">
                        <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="p.sinyal">
                        <option v-for="o in sinyalOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addPendukung"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removePendukung(i)" :disabled="pendukungRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Quantity (Tangki) -->
            <div class="mt-6 font-semibold mb-2">Quantity</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Alat Ukur</th>
                    <th class="px-3 py-2 text-xs uppercase">Merk</th>
                    <th class="px-3 py-2 text-xs uppercase">Tera</th>
                    <th class="px-3 py-2 text-xs uppercase">Masa Berlaku</th>
                    <th class="px-3 py-2 text-xs uppercase">Flowmeter tiap Pengiriman</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q,i) in quantityTangkiRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2"><FormInput v-model="q.alat" placeholder="Flowmeter"/></td>
                    <td class="px-3 py-2"><FormInput v-model="q.merk" placeholder="LC M10"/></td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="q.tera">
                        <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2"><FormInput v-model="q.masa" placeholder="Berlaku"/></td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="q.flowmeter">
                        <option value="Ya">Ya</option><option value="Tidak">Tidak</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addQuantityTangki"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeQuantityTangki(i)" :disabled="quantityTangkiRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Quality (Tangki) -->
            <div class="mt-6 font-semibold mb-2">Quality</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Min. Spec.</th>
                    <th class="px-3 py-2 text-xs uppercase">Uji Lab</th>
                    <th class="px-3 py-2 text-xs uppercase">COQ tiap Pengiriman</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q,i) in qualityTangkiRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="q.spec">
                        <option value="">- Pilihan -</option>
                        <option v-for="o in specOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="q.lab">
                        <option v-for="o in yaTidakOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2">
                      <FormSelect v-model="q.coq">
                        <option v-for="o in yaTidakOptions" :key="o" :value="o">{{ o }}</option>
                      </FormSelect>
                    </td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addQualityTangki"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeQualityTangki(i)" :disabled="qualityTangkiRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4">
              <FormLabel>Catatan</FormLabel>
              <FormTextarea v-model="form.catatan_tangki" rows="3" />
            </div>
          </div>
  
          <!-- Kapal -->
          <div>
            <div class="font-semibold mb-3">Informasi Pembongkaran Media Kapal</div>
  
            <!-- Kapal -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">No</th>
                    <th class="px-3 py-2 text-xs uppercase">Tipe</th>
                    <th class="px-3 py-2 text-xs uppercase">Kapasitas</th>
                    <th class="px-3 py-2 text-xs uppercase">Jumlah</th>
                    <th class="px-3 py-2 text-xs uppercase">Inlet Pipa</th>
                    <th class="px-3 py-2 text-xs uppercase">Ukuran</th>
                    <th class="px-3 py-2 text-xs uppercase">Metode</th>
                    <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(k,i) in kapalRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ i+1 }}</td>
                    <td class="px-3 py-2"><FormInput v-model="k.tipe" /></td>
                    <td class="px-3 py-2"><FormInput v-model="k.kapasitas" /></td>
                    <td class="px-3 py-2"><FormInput v-model="k.jumlah" /></td>
                    <td class="px-3 py-2"><FormInput v-model="k.inlet" /></td>
                    <td class="px-3 py-2"><FormInput v-model="k.ukuran" /></td>
                    <td class="px-3 py-2"><FormInput v-model="k.metode" /></td>
                    <td class="px-3 py-2 text-center space-x-2">
                      <Button size="sm" @click="addKapal"><Lucide icon="Plus" class="w-4 h-4"/></Button>
                      <Button size="sm" variant="danger" @click="removeKapal(i)" :disabled="kapalRows.length===1">
                        <Lucide icon="X" class="w-4 h-4"/>
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Jetty -->
            <div class="mt-6 font-semibold mb-2">Jetty</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">Max LOA</th>
                    <th class="px-3 py-2 text-xs uppercase">Min PBL</th>
                    <th class="px-3 py-2 text-xs uppercase">Draft (LWS)</th>
                    <th class="px-3 py-2 text-xs uppercase">Kekuatan (DWT)</th>
                    <th class="px-3 py-2 text-xs uppercase">Izin</th>
                    <th class="px-3 py-2 text-xs uppercase">Persyaratan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(j,i) in jettyRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ j.loa || '-' }}</td>
                    <td class="px-3 py-2">{{ j.pbl || '-' }}</td>
                    <td class="px-3 py-2">{{ j.lws || '-' }}</td>
                    <td class="px-3 py-2">{{ j.kekuatan || '-' }}</td>
                    <td class="px-3 py-2">{{ j.izin || '-' }}</td>
                    <td class="px-3 py-2">{{ j.syarat || '-' }}</td>
                  </tr>
                  <tr v-if="!(jettyRows?.length)">
                    <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Quantity (Kapal) -->
            <div class="mt-6 font-semibold mb-2">Quantity</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">Alat Ukur</th>
                    <th class="px-3 py-2 text-xs uppercase">Merk</th>
                    <th class="px-3 py-2 text-xs uppercase">Tera</th>
                    <th class="px-3 py-2 text-xs uppercase">Masa Berlaku</th>
                    <th class="px-3 py-2 text-xs uppercase">Flowmeter tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q,i) in quantityKapalRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ q.alat || '-' }}</td>
                    <td class="px-3 py-2">{{ q.merk || '-' }}</td>
                    <td class="px-3 py-2">{{ q.tera || '-' }}</td>
                    <td class="px-3 py-2">{{ q.masa || '-' }}</td>
                    <td class="px-3 py-2">{{ q.flowmeter || '-' }}</td>
                  </tr>
                  <tr v-if="!(quantityKapalRows?.length)">
                    <td colspan="5" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <!-- Quality (Kapal) -->
            <div class="mt-6 font-semibold mb-2">Quality</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-xs uppercase">Min. Spec.</th>
                    <th class="px-3 py-2 text-xs uppercase">Uji Lab</th>
                    <th class="px-3 py-2 text-xs uppercase">COQ tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q,i) in qualityKapalRows" :key="i" class="border-b">
                    <td class="px-3 py-2">{{ q.spec || '-' }}</td>
                    <td class="px-3 py-2">{{ q.lab || '-' }}</td>
                    <td class="px-3 py-2">{{ q.coq || '-' }}</td>
                  </tr>
                  <tr v-if="!(qualityKapalRows?.length)">
                    <td colspan="3" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4">
              <FormLabel>Catatan</FormLabel>
              <FormTextarea v-model="form.catatan_kapal" rows="3" />
            </div>
          </div>
        </div>
      </div>
  
      <!-- Footer note -->
      <div class="text-xs text-slate-500 mt-4">
        Harap mengisi data LCR dengan benar. Jika tombol save terkunci, pastikan Anda mengisi Latitude & Longitude.
      </div>
    </div>
  
    <!-- Modal Kelola Gambar -->
    <div v-if="mediaOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="mediaOpen=false"></div>
      <div class="relative bg-white w-[1000px] max-w-[95vw] rounded-lg shadow-lg">
        <div class="px-5 py-3 border-b flex items-center">
          <div class="font-semibold">Kelola Gambar LCR</div>
          <button class="ml-auto text-slate-500" @click="mediaOpen=false">✕</button>
        </div>
  
        <div class="p-5 space-y-8 max-h-[75vh] overflow-y-auto">
          <template
            v-for="section in [
              { key:'layout_lokasi', label:'Peta / Layout Pabrik / Site' },
              { key:'layout_bongkar', label:'Rute Pembongkaran' },
              { key:'kondisi_jalan', label:'Kondisi Jalan Menuju Lokasi' },
              { key:'kantor_perusahaan', label:'Pintu Gerbang & Kantor Perusahaan' },
              { key:'fasilitas_storage', label:'Fasilitas Penyimpanan' },
              { key:'inlet_pipa', label:'Inlet Pipa' },
              { key:'alat_ukur_gambar', label:'Alat Ukur' },
              { key:'media_datar', label:'Media Datar' },
              { key:'keterangan_lain', label:'Keterangan Penunjang Lain' },
            ]"
            :key="section.key"
          >
            <div class="font-semibold mb-2">{{ section.label }}</div>
  
            <div class="flex items-center gap-3 mb-3">
              <input type="file" multiple @change="onPick(section.key as any, $event)">
            </div>
  
            <div class="grid grid-cols-12 gap-3">
              <div
                v-for="(it, i) in (media as any)[section.key]"
                :key="i"
                class="col-span-12 md:col-span-6 lg:col-span-4 border rounded p-2"
              >
                <img :src="it.url" alt="" class="w-full h-40 object-cover rounded">
                <input
                  v-model="it.caption"
                  class="mt-2 w-full border rounded px-2 py-1"
                  placeholder="Keterangan gambar…"
                />
                <button class="mt-2 text-red-600 text-sm" @click="removeMedia(section.key as any, i)">Hapus</button>
              </div>
  
              <div v-if="!(media as any)[section.key]?.length" class="text-slate-500 col-span-12">
                Belum ada gambar.
              </div>
            </div>
          </template>
        </div>
  
        <div class="px-5 py-3 border-t flex items-center justify-end gap-2">
          <button class="btn btn-outline-secondary" @click="mediaOpen=false">Tutup</button>
          <button class="btn btn-primary" @click="saveMedia">Simpan</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  /* ===== Imports (PASTIKAN reactive DI-IMPORT) ===== */
  import {
    ref,
    reactive,
    computed,
    watch,
    onMounted,
    onBeforeUnmount,
    defineComponent,
    h,
    type PropType,
  } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { RouterLink, useRoute, useRouter } from 'vue-router'
  import { debounce } from 'lodash'
  
  import Button from '@/components/Base/Button'
  import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  /* ====== API helper untuk select remote ====== */
  const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  })
  
  export type SimpleOption<T = any> = {
    value: number
    label: string
    raw?: T
  }
  export interface CabangOption<T = any> extends SimpleOption<T> {
    id_wilayah: number | null
  }
  
  /* Customers */
  async function searchCustomers(search = '', perPage = 10): Promise<SimpleOption[]> {
    const { data } = await api.get('/customers', {
      params: { per_page: perPage, search: search || undefined },
    })
    const rows = data.data ?? data
    return rows.map((r: any) => ({
      value: r.id_customer,
      label: `${r.nama_perusahaan ?? r.email ?? r.user?.name ?? 'Customer'}`,
      raw: r,
    }))
  }
  async function getCustomerById(id: number): Promise<SimpleOption> {
    const { data } = await api.get(`/customers/${id}`)
    return {
      value: data.id_customer,
      label: `${data.nama_perusahaan ?? data.email ?? data.user?.name ?? 'Customer'}`,
      raw: data,
    }
  }
  
  /* Cabang */
  async function searchCabangs(search = '', perPage = 10): Promise<CabangOption[]> {
    const { data } = await api.get('/cabangs', {
      params: { per_page: perPage, search: search || undefined },
    })
    const rows = data.data ?? data
    return rows.map((r: any) => ({
      value: r.id_cabang,
      label: `${r.nama_cabang ?? 'Cabang'}`,
      id_wilayah: r.id_wilayah ?? null,
      raw: r,
    }))
  }
  async function getCabangById(id: number): Promise<CabangOption> {
    const { data } = await api.get(`/cabangs/${id}`)
    return {
      value: data.id_cabang,
      label: `${data.nama_cabang ?? 'Cabang'}`,
      id_wilayah: data.id_wilayah ?? null,
      raw: data,
    }
  }
  
  /* Wilayah OA */
  async function searchWilayahOa(search = '', perPage = 10): Promise<SimpleOption[]> {
    const { data } = await api.get('/wilayah-angkuts', {
      params: { per_page: perPage, search: search || undefined },
    })
    const rows = data.data ?? data
    return rows.map((r: any) => ({
      value: r.id,
      label: r.destinasi,
      raw: r,
    }))
  }
  async function getWilayahOaById(id: number): Promise<SimpleOption> {
    const { data } = await api.get(`/wilayah-angkuts/${id}`)
    return {
      value: data.id,
      label: data.destinasi ?? data.nama ?? data.wilayah ?? data.kota ?? `Wilayah OA (#${data.id})`,
      raw: data,
    }
  }
  
  /* ====== Komponen Select Remote ====== */
  const SearchableRemoteSelect = defineComponent({
    name: 'SearchableRemoteSelect',
    props: {
      modelValue: { type: Number as PropType<number | null>, default: null },
      fetcher: { type: Function as unknown as PropType<(q?: string) => Promise<SimpleOption[]>>, required: true },
      getById: { type: Function as unknown as PropType<(id: number) => Promise<SimpleOption>>, required: true },
      placeholder: { type: String, default: 'Pilih…' },
      disabled: { type: Boolean, default: false },
      invalid: { type: Boolean, default: false },
    },
    emits: ['update:modelValue', 'selected'],
    setup(props, { emit }) {
      const open = ref(false)
      const search = ref('')
      const options = ref<SimpleOption[]>([])
      const loading = ref(false)
      const selectedLabel = ref<string>('')
  
      const inputId = ref(`srsel-${Math.random().toString(36).slice(2)}`)
      const wrapperId = ref(`srwrap-${Math.random().toString(36).slice(2)}`)
  
      const load = async (q = '') => {
        loading.value = true
        try {
          options.value = await props.fetcher(q) || []
        } finally {
          loading.value = false
        }
      }
      const debouncedLoad = debounce((q: string) => { void load(q) }, 250)
  
      const setByIdLabel = async () => {
        if (props.modelValue != null) {
          try {
            const o = await props.getById(props.modelValue)
            selectedLabel.value = o?.label ?? ''
            if (o && !options.value.some(x => x.value === o.value)) {
              options.value = [o, ...options.value]
            }
          } catch {
            selectedLabel.value = ''
          }
        } else {
          selectedLabel.value = ''
        }
      }
  
      const toggle = async () => {
        if (props.disabled) return
        open.value = !open.value
        if (open.value) {
          await load('')
          queueMicrotask(() => (document.getElementById(inputId.value) as HTMLInputElement | null)?.focus())
        }
      }
      const pick = (o: SimpleOption) => {
        emit('update:modelValue', o.value)
        emit('selected', o)
        selectedLabel.value = o.label
        open.value = false
      }
  
      const onClickOutside = (e: MouseEvent) => {
        const root = document.getElementById(wrapperId.value)
        if (root && !root.contains(e.target as Node)) open.value = false
      }
  
      onMounted(() => {
        setByIdLabel()
        document.addEventListener('mousedown', onClickOutside)
      })
      onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
  
      watch(() => props.modelValue, setByIdLabel)
      watch(search, (v) => debouncedLoad(v))
  
      return () =>
        h('div', { id: wrapperId.value, class: 'relative' }, [
          h('button', {
            type: 'button',
            class:
              `w-full border rounded px-3 py-2 text-left hover:border-slate-400 ` +
              `${props.disabled ? 'opacity-60 cursor-not-allowed ' : ''}` +
              `${props.invalid ? ' border-danger ring-1 ring-danger/40 ' : ''}`,
            onClick: toggle,
          }, [
            h('span', { class: selectedLabel.value ? '' : 'text-slate-400' }, selectedLabel.value || props.placeholder),
            h('span', { class: 'float-right text-slate-400' }, [
              h(Lucide as any, { icon: 'ChevronDown', class: 'w-4 h-4 inline' })
            ])
          ]),
          open.value
            ? h('div', { class: 'absolute z-50 mt-1 w-full bg-white border rounded shadow' }, [
                h('div', { class: 'p-2 border-b' }, [
                  h('input', {
                    id: inputId.value,
                    value: search.value,
                    placeholder: 'Cari...',
                    class: 'w-full outline-none',
                    onInput: (e: Event) => (search.value = (e.target as HTMLInputElement).value),
                  })
                ]),
                h('div', { class: 'max-h-56 overflow-auto' }, [
                  loading.value
                    ? h('div', { class: 'p-3 text-sm text-slate-500' }, 'Memuat…')
                    : options.value.length === 0
                      ? h('div', { class: 'p-3 text-sm text-slate-500' }, 'Tidak ada hasil')
                      : options.value.map(o =>
                          h('div', {
                            key: String(o.value),
                            class: 'px-3 py-2 hover:bg-slate-100 cursor-pointer text-sm',
                            onClick: () => pick(o),
                          }, o.label)
                        )
                ])
              ])
            : null
        ])
    },
  })
  
  /* ====== Route & state ====== */
  const route = useRoute()
  const router = useRouter()
  const isEdit = computed(() => !!route.params.id)
  const idParam = computed(() => Number(route.params.id))
  
  const loading = ref(false)
  const mediaOpen = ref(false)
  
  const errors = ref<Record<string, string[]>>({})
  function firstError(field: string): string | null {
    if (!errors.value) return null
    if (errors.value[field]?.length) return errors.value[field][0]
    const key = Object.keys(errors.value).find(k => k === field || k.startsWith(field + '.'))
    return key ? (errors.value[key]?.[0] ?? null) : null
  }
  
  const form = ref<any>({
    // general
    id_lcr: null,
    id_customer: null,
    id_cabang: null,
    id_wilayah: null,
    id_wil_oa: null,
    alamat_survey: '',
    telp_survey: '',
    fax_survey: '',
    tgl_survey: '',
    review: '',
    jenis_usaha: '',
    website: '',
    // arrays
    nama_surveyor: [],
    hasilsurv: [],
    produkvol: [],
    picustomer: [],
    kompetitor: [],
    // komitmen & toleransi
    logistik_result: 1,
    toleransi: '',
    // lokasi
    latitude_lokasi: '',
    longitude_lokasi: '',
    link_google_maps: '',
    jarak_depot: '',
    rute_lokasi: '',
    note_lokasi: '',
    max_truk: '',
    min_vol_kirim: '',
    penjelasan_bongkar: '',
    // unloading
    tangki: [],
    pendukung: [],
    quantity_tangki: [],
    quality_tangki: [],
    catatan_tangki: '',
    kapal: [],
    jetty: [],
    quantity_kapal: [],
    quality_kapal: [],
    catatan_kapal: '',
    // flags (agar tombol media muncul saat edit)
    flag_approval: 0,
  })
  
  /* ====== Media (reactive) ====== */
  const media = reactive({
    layout_lokasi: [] as any[],
    layout_bongkar: [] as any[],
    kondisi_jalan: [] as any[],
    kantor_perusahaan: [] as any[],
    fasilitas_storage: [] as any[],
    inlet_pipa: [] as any[],
    alat_ukur_gambar: [] as any[],
    media_datar: [] as any[],
    keterangan_lain: [] as any[],
  })
  
  function hydrateMediaFromForm() {
    const keys = Object.keys(media) as (keyof typeof media)[]
    for (const k of keys) {
      const src = (form.value as any)[k]
      media[k] = Array.isArray(src) ? [...src] : []
    }
  }
  function openMediaModal() {
    hydrateMediaFromForm()
    mediaOpen.value = true
  }
  async function onPick(field: keyof typeof media, ev: Event) {
    const input = ev.target as HTMLInputElement
    const files = Array.from(input.files || [])
    for (const f of files) {
      const fd = new FormData()
      fd.append('file', f)
      const { data } = await axios.post('/api/uploads/lcr-image', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      media[field].push({ path: data.path, url: data.url, caption: '' })
    }
    input.value = ''
  }
  function removeMedia(field: keyof typeof media, idx: number) {
    media[field].splice(idx, 1)
  }
  async function saveMedia() {
    try {
      await axios.patch(`/api/customer-lcrs/${idParam.value}`, {
        layout_lokasi: media.layout_lokasi,
        layout_bongkar: media.layout_bongkar,
        kondisi_jalan: media.kondisi_jalan,
        kantor_perusahaan: media.kantor_perusahaan,
        fasilitas_storage: media.fasilitas_storage,
        inlet_pipa: media.inlet_pipa,
        alat_ukur_gambar: media.alat_ukur_gambar,
        media_datar: media.media_datar,
        keterangan_lain: media.keterangan_lain,
      })
      Object.assign(form.value, media)
      mediaOpen.value = false
      Swal.fire({ icon: 'success', title: 'Gambar disimpan' })
    } catch (e: any) {
      Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: e?.response?.data?.message || e.message })
    }
  }
  
  /* ====== Options ====== */
  const jenisUsahaOptions = ['Manufacture','Trading','Retail','Transport','Bunker','Lainnya']
  const maxTrukOptions = ['8 KL', '16 KL', '24 KL', '32 KL']
  const tangkiTypeOptions = ['Fixed', 'Mobile']
  const inletOptions = ['Manhole', 'Pipa', 'Quick Coupling']
  const ukuranOptions = ['1 In', '1.5 In', '2 In', '3 In', '4 In']
  const pompaOptions = ['Transportir','Customer']
  const adaTidakOptions = ['Ada','Tidak']
  const yaTidakOptions = ['Ya','Tidak']
  const sinyalOptions = ['Telkomsel','Indosat','XL','3','Smartfren','Lainnya']
  const specOptions = ['Migas','Non Migas']
  
  /* ====== Row states ====== */
  const surveyorRows = ref<string[]>([''])
  const hasilRows = ref<string[]>([''])
  const produkVolRows = ref<{produk: string, volbul: string}[]>([{produk:'', volbul:''}])
  const picRows = ref<{nama:string,posisi:string,telepon:string}[]>([{nama:'',posisi:'',telepon:''}])
  const kompetitorRows = ref<string[]>([''])
  
  const tangkiRows = ref<any[]>([{tipe:'', kapasitas:'', jumlah:'', produk:'', inlet:'', ukuran:''}])
  const pendukungRows = ref<any[]>([{pompa:'', aliran:'', selang:'', valve:'', ground:'', sinyal:''}])
  const quantityTangkiRows = ref<any[]>([{alat:'', merk:'', tera:'', masa:'', flowmeter:''}])
  const qualityTangkiRows = ref<any[]>([{spec:'', lab:'', coq:''}])
  
  const kapalRows = ref<any[]>([{tipe:'', kapasitas:'', jumlah:'', inlet:'', ukuran:'', metode:''}])
  const jettyRows = ref<any[]>([{loa:'', pbl:'', lws:'', kekuatan:'', izin:'', syarat:''}])
  const quantityKapalRows = ref<any[]>([{alat:'', merk:'', tera:'', masa:'', flowmeter:''}])
  const qualityKapalRows = ref<any[]>([{spec:'', lab:'', coq:''}])
  
  /* ====== Add/Remove helpers ====== */
  const addSurveyor = () => surveyorRows.value.push('')
  const removeSurveyor = (i:number) => surveyorRows.value.splice(i,1)
  const addHasil = () => hasilRows.value.push('')
  const removeHasil = (i:number) => hasilRows.value.splice(i,1)
  const addProdukVol = () => produkVolRows.value.push({produk:'', volbul:''})
  const removeProdukVol = (i:number) => produkVolRows.value.splice(i,1)
  const addPic = () => picRows.value.push({nama:'',posisi:'',telepon:''})
  const removePic = (i:number) => picRows.value.splice(i,1)
  const addKompetitor = () => kompetitorRows.value.push('')
  const removeKompetitor = (i:number) => kompetitorRows.value.splice(i,1)
  const addTangki = () => tangkiRows.value.push({tipe:'', kapasitas:'', jumlah:'', produk:'', inlet:'', ukuran:''})
  const removeTangki = (i:number) => tangkiRows.value.splice(i,1)
  const addPendukung = () => pendukungRows.value.push({pompa:'', aliran:'', selang:'', valve:'', ground:'', sinyal:''})
  const removePendukung = (i:number) => pendukungRows.value.splice(i,1)
  const addQuantityTangki = () => quantityTangkiRows.value.push({alat:'', merk:'', tera:'', masa:'', flowmeter:''})
  const removeQuantityTangki = (i:number) => quantityTangkiRows.value.splice(i,1)
  const addQualityTangki = () => qualityTangkiRows.value.push({spec:'', lab:'', coq:''})
  const removeQualityTangki = (i:number) => qualityTangkiRows.value.splice(i,1)
  const addKapal = () => kapalRows.value.push({tipe:'', kapasitas:'', jumlah:'', inlet:'', ukuran:'', metode:''})
  const removeKapal = (i:number) => kapalRows.value.splice(i,1)
  const addJetty = () => jettyRows.value.push({loa:'', pbl:'', lws:'', kekuatan:'', izin:'', syarat:''})
  const removeJetty = (i:number) => jettyRows.value.splice(i,1)
  const addQuantityKapal = () => quantityKapalRows.value.push({alat:'', merk:'', tera:'', masa:'', flowmeter:''})
  const removeQuantityKapal = (i:number) => quantityKapalRows.value.splice(i,1)
  const addQualityKapal = () => qualityKapalRows.value.push({spec:'', lab:'', coq:''})
  const removeQualityKapal = (i:number) => qualityKapalRows.value.splice(i,1)
  
  /* ====== Map Preview ====== */
  const mapUrl = computed(() => {
    const lat = parseFloat(form.value.latitude_lokasi)
    const lon = parseFloat(form.value.longitude_lokasi)
    if (!Number.isFinite(lat) || !Number.isFinite(lon)) return ''
    const zoom = 15
    const q = encodeURIComponent(`${lat},${lon}`)
    return `https://maps.google.com/maps?q=${q}&z=${zoom}&output=embed`
  })
  const buildMapLink = () => {
    const lat = form.value.latitude_lokasi
    const lon = form.value.longitude_lokasi
    if (lat && lon) {
      form.value.link_google_maps = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`
    }
  }
  
  /* ====== Auto isi id_wilayah dari cabang ====== */
  watch(() => form.value.id_cabang, async (id) => {
    if (!id) { form.value.id_wilayah = null; return }
    try {
      const cb = await getCabangById(id)
      form.value.id_wilayah = cb?.id_wilayah ?? null
    } catch {
      form.value.id_wilayah = null
    }
  })
  
  /* ====== Load data untuk edit ====== */
  onMounted(async () => {
    if (!isEdit.value) return
    loading.value = true
    try {
      const { data } = await axios.get(`/api/customer-lcrs/${idParam.value}`)
      Object.assign(form.value, data, { id_lcr: idParam.value })
  
      // hydrate rows
      surveyorRows.value = data.nama_surveyor?.length ? data.nama_surveyor : ['']
      hasilRows.value    = data.hasilsurv?.length     ? data.hasilsurv     : ['']
      produkVolRows.value= data.produkvol?.length    ? data.produkvol     : [{produk:'', volbul:''}]
      picRows.value      = data.picustomer?.length   ? data.picustomer    : [{nama:'',posisi:'',telepon:''}]
      kompetitorRows.value= data.kompetitor?.length  ? data.kompetitor    : ['']
  
      tangkiRows.value   = data.tangki?.length       ? data.tangki        : [{tipe:'', kapasitas:'', jumlah:'', produk:'', inlet:'', ukuran:''}]
      pendukungRows.value= data.pendukung?.length    ? data.pendukung     : [{pompa:'', aliran:'', selang:'', valve:'', ground:'', sinyal:''}]
      quantityTangkiRows.value = data.quantity_tangki?.length ? data.quantity_tangki : [{alat:'', merk:'', tera:'', masa:'', flowmeter:''}]
      qualityTangkiRows.value  = data.quality_tangki?.length  ? data.quality_tangki  : [{spec:'', lab:'', coq:''}]
  
      kapalRows.value    = data.kapal?.length        ? data.kapal         : [{tipe:'', kapasitas:'', jumlah:'', inlet:'', ukuran:'', metode:''}]
      jettyRows.value    = data.jetty?.length        ? data.jetty         : [{loa:'', pbl:'', lws:'', kekuatan:'', izin:'', syarat:''}]
      quantityKapalRows.value  = data.quantity_kapal?.length ? data.quantity_kapal : [{alat:'', merk:'', tera:'', masa:'', flowmeter:''}]
      qualityKapalRows.value   = data.quality_kapal?.length  ? data.quality_kapal  : [{spec:'', lab:'', coq:''}]
    } finally {
      loading.value = false
    }
  })
  
  /* ====== Submit ====== */
  async function submit() {
    if (!form.value.id_customer) {
      await Swal.fire({ icon: 'warning', title: 'Customer wajib dipilih' })
      return
    }
  
    // map rows -> payload
    form.value.nama_surveyor   = surveyorRows.value
    form.value.hasilsurv       = hasilRows.value
    form.value.produkvol       = produkVolRows.value
    form.value.picustomer      = picRows.value
    form.value.kompetitor      = kompetitorRows.value
    form.value.tangki          = tangkiRows.value
    form.value.pendukung       = pendukungRows.value
    form.value.quantity_tangki = quantityTangkiRows.value
    form.value.quality_tangki  = qualityTangkiRows.value
    form.value.kapal           = kapalRows.value
    form.value.jetty           = jettyRows.value
    form.value.quantity_kapal  = quantityKapalRows.value
    form.value.quality_kapal   = qualityKapalRows.value
  
    loading.value = true
    errors.value = {}
  
    try {
      if (isEdit.value) {
        await axios.put(`/api/customer-lcrs/${idParam.value}`, form.value)
        await Swal.fire({ icon: 'success', title: 'Updated' })
      } else {
        await axios.post('/api/customer-lcrs', form.value)
        await Swal.fire({ icon: 'success', title: 'Created' })
      }
      router.push({ name: 'lcr-list' })
    } catch (e: any) {
      const status = e?.response?.status
      if (status === 422) {
        errors.value = e.response?.data?.errors || {}
        const firstMsg = Object.values(errors.value)[0]?.[0] || 'Periksa kembali input Anda.'
        await Swal.fire({ icon: 'error', title: 'Validasi gagal', text: firstMsg })
        window.scrollTo({ top: 0, behavior: 'smooth' })
        return
      }
      await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: e?.response?.data?.message || e.message })
    } finally {
      loading.value = false
    }
  }
  </script>
  