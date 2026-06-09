<template>
  <div class="flex flex-col overflow-hidden bg-gray-100" style="height: calc(100vh - 60px)">

    <!-- Top bar -->
    <div class="flex items-center gap-2 px-4 py-2 bg-white border-b border-gray-200 shrink-0 flex-wrap">
      <router-link to="/sales" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
        <ArrowLeftIcon class="w-4 h-4" /> Bills
      </router-link>
      <span class="text-gray-300 shrink-0">/</span>
      <h2 class="text-sm font-semibold text-gray-800 shrink-0">New Bill</h2>
      <div class="ml-auto shrink-0 flex items-center gap-1.5">
        <router-link v-if="lastReceiptId" :to="`/sales/${lastReceiptId}`"
          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors">
          <PrinterIcon class="w-3.5 h-3.5" /> Last Receipt
        </router-link>
        <button v-if="kbShortcutsEnabled" @click="showKbHelp = !showKbHelp" type="button"
          class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
          <QuestionMarkCircleIcon class="w-4 h-4" /><span class="hidden sm:inline">Shortcuts</span>
        </button>
      </div>

      <!-- Draft tabs -->
      <div v-if="draftBills.length" class="flex items-center gap-1.5 ml-2 flex-wrap">
        <span class="text-xs text-gray-400 font-medium shrink-0">Drafts:</span>
        <button
          v-for="draft in draftBills"
          :key="draft.id"
          @click="loadDraft(draft)"
          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
          :class="activeDraftId === draft.id
            ? 'bg-amber-500 text-white border-amber-500'
            : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-50'"
        >
          <span class="font-mono">{{ draft.invoice_number }}</span>
          <span v-if="draft.table_number" class="opacity-60">· {{ draft.table_number }}</span>
        </button>
        <button
          v-if="activeDraftId"
          @click="resetForm"
          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 hover:bg-gray-200 border border-gray-200"
        >
          <PlusIcon class="w-3 h-3" /> New
        </button>
      </div>
    </div>

    <!-- Main 3-column layout: category sidebar + product grid + bill -->
    <div class="flex flex-1 overflow-hidden">

      <!-- ── Category sidebar (always visible) ── -->
      <div class="flex flex-col w-[4.5rem] bg-white border-r border-gray-200 overflow-y-auto shrink-0">
        <!-- Collapse/expand toggle -->
        <button
          @click="showProductPanel = !showProductPanel"
          type="button"
          class="flex items-center justify-center py-2.5 border-b border-gray-100 text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors shrink-0"
          :title="showProductPanel ? 'Hide products' : 'Show products'"
        >
          <span class="text-base font-bold">{{ showProductPanel ? '◀' : '▶' }}</span>
        </button>
        <!-- Category buttons -->
        <button
          v-for="(cat, idx) in categoryTabs"
          :key="cat"
          @click="activeCategory = cat; showProductPanel = true"
          class="flex flex-col items-center justify-center gap-0.5 px-1 py-2.5 text-center transition-all border-b border-gray-100 shrink-0 relative"
          :class="activeCategory === cat
            ? 'bg-amber-50 text-amber-700'
            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'"
        >
          <span v-if="activeCategory === cat" class="absolute left-0 inset-y-0 w-0.5 bg-amber-500 rounded-r"></span>
          <span class="text-xl leading-none">{{ getCategoryEmoji(cat) }}</span>
          <span class="text-[8px] font-semibold leading-tight mt-0.5 w-full text-center line-clamp-2 px-0.5">{{ cat }}</span>
        </button>
      </div>

      <!-- ── LEFT: Product browser (collapsible) ── -->
      <div v-show="showProductPanel" class="flex flex-col flex-1 overflow-hidden bg-white border-r border-gray-200">

        <!-- Search + barcode -->
        <div class="flex gap-2 px-3 py-2.5 border-b border-gray-100 shrink-0">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input
              ref="searchInputRef"
              v-model="productGridSearch"
              type="text"
              :placeholder="kbShortcutsEnabled ? 'Search… (F1)' : 'Search products…'"
              class="w-full pl-9 pr-3 py-2.5 rounded-xl text-sm bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
              @input="onSearchInput"
            />
          </div>
          <div class="relative">
            <QrCodeIcon class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input
              ref="barcodeInputRef"
              v-model="barcodeInput"
              type="text"
              :placeholder="kbShortcutsEnabled ? 'Barcode (F2)' : 'Barcode'"
              class="pl-8 pr-3 py-2.5 rounded-xl text-sm bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 focus:outline-none focus:border-amber-500 w-28"
              @keyup.enter="scanBarcode"
            />
          </div>
          <button type="button" @click="openScanner" title="Camera scanner"
            class="w-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-400 hover:border-amber-400 hover:text-amber-600 flex items-center justify-center shrink-0 transition-colors">
            <QrCodeIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Barcode error -->
        <p v-if="barcodeError" class="mx-3 mt-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded-xl px-3 py-2 flex items-center gap-1.5 shrink-0">
          <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ barcodeError }}
        </p>

        <!-- Product grid -->
        <div class="flex-1 overflow-y-auto p-3 bg-gray-50">
          <div v-if="!gridProducts.length" class="flex flex-col items-center justify-center py-20 text-gray-400">
            <ShoppingBagIcon class="w-16 h-16 opacity-20 mb-3" />
            <p class="text-sm font-medium">No products found</p>
          </div>
          <div class="grid grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
            <button
              v-for="(product, idx) in gridProducts"
              :key="product.id"
              :data-grid-idx="idx"
              @click="addProductFromGrid(product)"
              :disabled="isStockTracked(product) && product.stock_quantity < 1 && !(product.open_bottles_remaining_ml > 0)"
              type="button"
              class="relative flex flex-col rounded-2xl border-2 text-left transition-all select-none overflow-hidden group bg-white"
              :class="isStockTracked(product) && product.stock_quantity < 1 && !(product.open_bottles_remaining_ml > 0)
                ? 'border-gray-100 opacity-40 cursor-not-allowed'
                : gridFocusIndex === idx
                  ? 'border-amber-500 ring-2 ring-amber-400 shadow-xl shadow-amber-100'
                  : isInBill(product.id)
                    ? 'border-amber-400 shadow-lg shadow-amber-100'
                    : 'border-gray-200 hover:border-amber-300 hover:shadow-md active:scale-95'"
            >
              <!-- In-bill qty badge -->
              <div
                v-if="isInBill(product.id)"
                class="absolute top-2 right-2 z-10 w-7 h-7 bg-amber-500 text-white rounded-full text-sm font-bold flex items-center justify-center shadow-lg"
              >{{ getBillQty(product.id) }}</div>

              <!-- Image -->
              <div class="w-full aspect-[4/3] overflow-hidden bg-gray-100 shrink-0 relative">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                  <ShoppingBagIcon class="w-10 h-10 text-gray-300" />
                </div>
                <!-- Overlay quick-add button -->
                <div class="absolute inset-x-0 bottom-0 py-2 px-2 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex justify-end"
                     v-if="!(isStockTracked(product) && product.stock_quantity < 1 && !(product.open_bottles_remaining_ml > 0))">
                  <span class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">+</span>
                </div>
              </div>

              <div class="p-2.5 flex flex-col gap-1">
                <p class="text-sm font-semibold text-gray-800 leading-tight line-clamp-2">{{ product.name }}</p>
                <p class="text-base font-bold text-amber-600">LKR {{ lkr(product.selling_price) }}</p>
                <div v-if="product.shot_variants?.length" class="flex flex-wrap gap-1 mt-0.5">
                  <span v-for="v in product.shot_variants" :key="v.name"
                    class="text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-200 rounded-full px-1.5 py-0.5">
                    {{ v.name }} LKR {{ lkr(v.price) }}
                  </span>
                </div>
                <p v-if="isStockTracked(product)" class="text-xs"
                  :class="product.stock_quantity < 1 && product.open_bottles_remaining_ml > 0 ? 'text-blue-500' : product.stock_quantity <= product.min_stock_level ? 'text-red-500' : 'text-gray-400'">
                  <template v-if="product.stock_quantity < 1 && product.open_bottles_remaining_ml > 0">
                    ~{{ Math.round(product.open_bottles_remaining_ml) }}ml open
                  </template>
                  <template v-else>
                    {{ product.stock_quantity <= product.min_stock_level ? '⚠ ' : '' }}{{ product.stock_quantity }} in stock
                  </template>
                </p>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Order panel ── -->
      <div class="flex flex-col w-[50%] shrink-0 overflow-hidden bg-gray-50">

        <!-- Table + Customer -->
        <div class="px-3 pt-3 pb-2 bg-white border-b border-gray-200 shrink-0 space-y-2">
          <!-- Table picker button -->
          <div class="flex gap-2">
            <button
              @click="showTablePicker = true"
              type="button"
              class="flex-1 flex items-center justify-between px-3 py-2.5 rounded-xl border-2 transition-all font-semibold text-sm"
              :class="form.table_number
                ? 'border-amber-500 bg-amber-50 text-amber-700'
                : 'border-gray-200 bg-gray-50 text-gray-500 hover:border-amber-300'"
            >
              <div class="flex items-center gap-2">
                <TableCellsIcon class="w-4 h-4" />
                <span>{{ form.table_number ? 'Table ' + form.table_number : 'Select Table' }}</span>
              </div>
              <span v-if="form.table_number" @click.stop="form.table_number = ''" class="text-gray-400 hover:text-red-500 text-lg leading-none">×</span>
              <ChevronDownIcon v-else class="w-4 h-4 text-gray-400" />
            </button>

            <div class="flex gap-1 flex-1">
              <select v-model="form.customer_id" class="flex-1 min-w-0 px-2 py-2.5 rounded-xl border-2 border-gray-200 bg-gray-50 text-sm text-gray-700 focus:outline-none focus:border-amber-500 font-medium">
                <option value="">👤 Walk-in</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <button
                @click="showNewCustomer = !showNewCustomer"
                type="button"
                class="w-10 shrink-0 rounded-xl border-2 border-gray-200 flex items-center justify-center text-gray-500 hover:border-amber-400 hover:text-amber-600 bg-gray-50"
                title="Add new customer"
              >
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Quick new customer -->
          <div v-if="showNewCustomer" class="bg-blue-50 border border-blue-200 rounded-xl p-2.5 space-y-1.5">
            <div class="grid grid-cols-2 gap-1.5">
              <input v-model="newCustomer.name" type="text" placeholder="Name *" class="px-3 py-2 rounded-lg border border-blue-200 text-sm focus:outline-none focus:border-blue-400" @keyup.enter="saveNewCustomer" />
              <input v-model="newCustomer.phone" type="tel" placeholder="Phone" class="px-3 py-2 rounded-lg border border-blue-200 text-sm focus:outline-none focus:border-blue-400" @keyup.enter="saveNewCustomer" />
            </div>
            <p v-if="newCustomerError" class="text-xs text-red-600">{{ newCustomerError }}</p>
            <button @click="saveNewCustomer" :disabled="savingCustomer || !newCustomer.name.trim()" class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-semibold">
              {{ savingCustomer ? 'Saving…' : 'Save & Select' }}
            </button>
          </div>

        </div>

        <!-- Bill items (scrollable) -->
        <div class="flex-1 overflow-y-auto bg-white">
          <div v-if="!form.items.length" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <ShoppingCartIcon class="w-14 h-14 opacity-15 mb-3" />
            <p class="text-base font-medium text-gray-400">Tap products to add</p>
            <p class="text-sm text-gray-300 mt-1">Items will appear here</p>
          </div>

          <div v-else>
            <!-- Order summary header -->
            <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-100">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Order · {{ form.items.filter(i => i.product_id).length }} items</span>
              <span class="text-xs text-gray-400">{{ form.items.filter(i => i.product_id).reduce((s, i) => s + i.quantity, 0) }} qty</span>
            </div>

            <div class="divide-y divide-gray-100">
              <div
                v-for="(item, i) in form.items"
                :key="i"
                class="px-3 py-3 hover:bg-gray-50 transition-colors"
              >
                <!-- If no product selected yet (manual line) -->
                <div v-if="!item.product_id" class="space-y-1.5">
                  <select v-model="item.product_id" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-amber-500" @change="fillProduct(item)">
                    <option value="">— Select product —</option>
                    <option v-for="p in products" :key="p.id" :value="p.id" :disabled="isStockTracked(p) && p.stock_quantity < 1 && !(p.open_bottles_remaining_ml > 0)">
                      {{ p.name }} {{ isStockTracked(p) ? '(' + p.stock_quantity + ')' : '' }}
                    </option>
                  </select>
                  <button @click="removeItem(i)" class="text-xs text-red-400 hover:text-red-600">Remove</button>
                </div>

                <!-- Product line -->
                <template v-else>
                  <div class="flex items-center gap-2.5">
                    <!-- Thumbnail -->
                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                      <img v-if="item.product_ref?.image" :src="item.product_ref.image" :alt="item.product_ref?.name" class="w-full h-full object-cover" />
                      <div v-else class="w-full h-full flex items-center justify-center">
                        <ShoppingBagIcon class="w-6 h-6 text-gray-300" />
                      </div>
                    </div>

                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-bold text-gray-800 leading-tight truncate">
                        {{ item.product_ref?.name || 'Product' }}
                      </p>
                      <p class="text-xs text-amber-600 font-semibold mt-0.5">
                        LKR {{ lkr(getEffectiveUnitPrice(item)) }}
                        <span v-if="item.selected_shot_variant" class="text-purple-500 font-normal"> · {{ item.selected_shot_variant.name }}</span>
                        <span v-else-if="item.serving_ml > 0 && !item.open_bottle_id" class="text-gray-400 font-normal"> · {{ item.serving_ml }}ml</span>
                      </p>
                    </div>

                    <!-- Qty control - larger touch targets -->
                    <div class="flex items-center gap-1.5 shrink-0">
                      <button
                        @click="decrementItem(item, i)"
                        :disabled="!!item.open_bottle_id"
                        class="w-9 h-9 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center font-bold text-xl leading-none transition-colors disabled:opacity-30 disabled:cursor-not-allowed border border-red-100"
                      >−</button>
                      <span class="w-8 text-center text-base font-bold text-gray-900">{{ item.quantity }}</span>
                      <button
                        @click="item.quantity++; recalcItem(item)"
                        :disabled="!!item.open_bottle_id || maxShotsFromOpenBottle(item) !== null && item.quantity >= maxShotsFromOpenBottle(item)"
                        class="w-9 h-9 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl leading-none transition-colors disabled:opacity-30 disabled:cursor-not-allowed border border-amber-200"
                      >+</button>
                    </div>

                    <!-- Line total -->
                    <div class="shrink-0 w-20 text-right">
                      <p class="text-sm font-bold text-gray-900">LKR {{ lkr(item._lineTotal) }}</p>
                    </div>

                    <button @click="removeItem(i)" class="shrink-0 w-7 h-7 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>

                  <!-- Extras row -->
                  <div class="mt-2 space-y-1.5">
                    <!-- Item discount -->
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 items-center">
                      <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-400">Disc:</span>
                        <input v-model.number="item.discount" type="number" min="0" class="w-16 px-2 py-1 rounded-lg border border-gray-200 text-xs text-center focus:outline-none focus:border-amber-400" @input="recalcItem(item)" @wheel.prevent placeholder="0" />
                      </div>

                      <!-- Opened bottle badge -->
                      <span v-if="item.open_bottle_id" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                        🍾 Opened · {{ item.open_bottle_ref?.remaining_volume_ml?.toFixed(0) }}ml
                      </span>

                      <!-- Shot variants -->
                      <template v-if="item.product_ref?.shot_variants?.length > 0">
                        <template v-if="item.open_bottle_id">
                          <div class="flex items-center gap-1">
                            <span class="text-xs text-gray-400">Price:</span>
                            <input v-model.number="item.unit_price" type="number" min="0" class="w-20 px-2 py-1 rounded-lg border border-gray-200 text-xs text-center focus:outline-none focus:border-amber-400" @input="recalcItem(item)" @wheel.prevent />
                          </div>
                          <button @click="clearOpenBottle(item)" type="button" class="text-xs text-red-400 hover:text-red-600">✕ Clear</button>
                        </template>
                        <template v-else>
                          <button v-for="v in item.product_ref.shot_variants" :key="v.name"
                            @click="selectShotVariant(item, v)"
                            type="button"
                            class="px-2 py-1 rounded-full text-xs font-semibold border transition-colors"
                            :class="item.selected_shot_variant?.name === v.name
                              ? 'bg-purple-600 text-white border-purple-600'
                              : 'bg-purple-50 text-purple-600 border-purple-200 hover:bg-purple-100'"
                          >{{ v.name }} · LKR {{ lkr(v.price) }}</button>
                          <span v-if="maxShotsFromOpenBottle(item) !== null"
                            class="px-1.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200">
                            {{ maxShotsFromOpenBottle(item) }} shots left
                          </span>
                          <button v-if="item.selected_shot_variant"
                            @click="item.selected_shot_variant = null; recalcItem(item)"
                            type="button"
                            class="text-xs text-gray-400 hover:text-gray-600">✕ Bottle</button>
                        </template>
                      </template>

                      <!-- Open bottle (liquor types only) -->
                      <template v-if="(['Liquor','Whisky','Vodka'].includes(item.product_ref?.product_type) || ['Hard Liquor','Foreign Liquor'].includes(item.product_ref?.category?.name)) && !item.open_bottle_id">
                        <button @click="showOpenBottlePicker(item)" type="button"
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 transition-colors"
                        >Sell opened bottle</button>
                      </template>

                      <!-- Bottle deposit -->
                      <div v-if="item.product_ref?.bottle_deposit_required" class="flex items-center gap-1.5">
                        <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                          <input type="checkbox" v-model="item.empty_bottle_returned" @change="recalcItem(item)" class="rounded text-amber-600" />
                          Bottle returned
                        </label>
                      </div>
                    </div>

                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Add manual line -->
          <div class="px-3 py-2">
            <button @click="addItem" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed border-gray-200 text-gray-400 hover:border-amber-400 hover:text-amber-500 text-sm font-medium transition-colors">
              <PlusIcon class="w-4 h-4" /> Add item manually
            </button>
          </div>
        </div>

        <!-- ── Totals + Payment (pinned) ── -->
        <div class="shrink-0 bg-white border-t-2 border-gray-200">

            <!-- Order summary / Total -->
            <div class="px-4 py-3 bg-amber-50 border-t border-amber-100">
              <button
                @click="showPricingDetails = !showPricingDetails"
                class="w-full flex items-center justify-between"
              >
                <div class="flex flex-col items-start">
                  <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Total Amount</span>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span v-if="form.discount > 0" class="text-xs text-red-600 bg-red-100 px-2 py-0.5 rounded-full">−{{ lkr(form.discount) }} off</span>
                    <span v-if="form.tax > 0" class="text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">+{{ form.tax_rate }}% tax</span>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-3xl font-black text-amber-600">LKR {{ lkr(total) }}</span>
                  <span class="text-amber-400 text-sm">{{ showPricingDetails ? '▲' : '▼' }}</span>
                </div>
              </button>

              <!-- Collapsible: subtotal / discount / tax -->
              <div v-if="showPricingDetails" class="mt-3 pt-3 border-t border-amber-200 space-y-2">
                <div class="flex justify-between text-sm text-gray-500">
                  <span>Subtotal</span><span class="text-gray-800 font-semibold">LKR {{ lkr(subtotal) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Discount</span>
                    <button
                      type="button" @click="toggleDiscountType"
                      class="px-2 py-1 text-xs font-bold rounded-lg border transition-colors shrink-0"
                      :class="discountType === 'percent'
                        ? 'bg-red-500 text-white border-red-500'
                        : 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200'"
                    >{{ discountType === 'percent' ? '%' : 'LKR' }}</button>
                    <input
                      v-model.number="discountInput"
                      type="number" min="0" :max="discountType === 'percent' ? 100 : undefined"
                      class="w-24 px-2 py-1 rounded-lg bg-white border border-gray-200 text-gray-800 text-sm text-center focus:outline-none focus:border-amber-500"
                      @input="applyDiscount" @wheel.prevent placeholder="0"
                    />
                  </div>
                  <span v-if="form.discount > 0" class="text-sm text-red-500 font-semibold">
                    −LKR {{ lkr(form.discount) }}
                    <span v-if="discountType === 'percent'" class="text-gray-400 text-xs font-normal">({{ discountInput }}%)</span>
                  </span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Tax</span>
                    <select v-model="selectedTaxId" class="px-2 py-1 rounded-lg bg-white border border-gray-200 text-gray-700 text-sm focus:outline-none focus:border-amber-500 w-36" @change="applyTax">
                      <option value="">None</option>
                      <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
                    </select>
                  </div>
                  <span v-if="form.tax > 0" class="text-sm text-blue-500 font-semibold">+LKR {{ lkr(form.tax) }}</span>
                </div>
              </div>
            </div>

            <!-- Payment method buttons (compact) -->
            <div class="px-3 pt-2 pb-1.5">
              <div class="flex gap-1.5">
                <template v-if="!splitPayment">
                  <button
                    v-for="opt in paymentOptions"
                    :key="opt.value"
                    @click="form.payment_method = opt.value"
                    class="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl font-bold text-sm transition-all border-2"
                    :class="form.payment_method === opt.value
                      ? 'bg-gray-900 text-white border-gray-900 shadow-lg'
                      : 'bg-gray-50 text-gray-500 border-gray-200 hover:border-gray-400'"
                  >
                    <span>{{ opt.icon }}</span>
                    <span>{{ opt.label }}</span>
                  </button>
                </template>
                <button
                  @click="toggleSplit"
                  class="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl font-bold text-sm transition-all border-2"
                  :class="splitPayment
                    ? 'bg-purple-600 text-white border-purple-600 shadow-lg'
                    : 'bg-gray-50 text-gray-500 border-gray-200 hover:border-purple-400'"
                >
                  <span>✂️</span>
                  <span>Split</span>
                </button>
                <button
                  @click="form.payment_status = form.payment_status === 'pending' ? 'paid' : form.payment_status === 'paid' ? 'partial' : 'pending'"
                  class="flex-1 flex items-center justify-center gap-1 py-2 rounded-xl font-bold text-sm transition-all border-2"
                  :class="form.payment_status === 'pending'
                    ? 'bg-yellow-50 text-yellow-700 border-yellow-300'
                    : form.payment_status === 'partial'
                      ? 'bg-orange-50 text-orange-700 border-orange-300'
                      : 'bg-green-50 text-green-700 border-green-300'"
                >
                  <span>{{ form.payment_status === 'pending' ? '⏳' : form.payment_status === 'partial' ? '⚡' : '✅' }}</span>
                  <span>{{ form.payment_status === 'pending' ? 'Pending' : form.payment_status === 'partial' ? 'Partial' : 'Paid' }}</span>
                </button>
              </div>
            </div>

            <!-- Card reference -->
            <div v-if="!splitPayment && form.payment_method === 'card'" class="px-3 pb-2">
              <input v-model="form.card_reference" type="text" placeholder="Card receipt reference…"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-amber-500 text-gray-700" />
            </div>

            <!-- Split payment inputs -->
            <div v-if="splitPayment" class="px-3 pb-2 space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-xs font-bold text-gray-500 mb-1 block">💵 Cash</label>
                  <input v-model.number="splitCash" type="number" min="0" step="1"
                    class="w-full px-3 py-2.5 rounded-xl border-2 border-gray-200 text-lg font-bold text-center text-gray-900 focus:outline-none focus:border-amber-500"
                    @input="onSplitCashInput" @wheel.prevent />
                </div>
                <div>
                  <label class="text-xs font-bold text-gray-500 mb-1 block">💳 Card</label>
                  <input v-model.number="splitCard" type="number" min="0" step="1"
                    class="w-full px-3 py-2.5 rounded-xl border-2 border-gray-200 text-lg font-bold text-center text-gray-900 focus:outline-none focus:border-amber-500"
                    @input="onSplitCardInput" @wheel.prevent />
                </div>
              </div>
              <div class="flex gap-1.5">
                <button @click="splitCash = total; splitCard = 0"
                  class="flex-1 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">All Cash</button>
                <button @click="splitCard = total; splitCash = 0"
                  class="flex-1 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">All Card</button>
                <button @click="splitCash = Math.ceil(total / 2); splitCard = Math.floor(total / 2)"
                  class="flex-1 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">50/50</button>
              </div>
              <!-- Card reference when card portion > 0 -->
              <div v-if="splitCard > 0">
                <input v-model="splitCardReference" type="text" placeholder="Card receipt / last 4 digits…"
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-amber-500 bg-white" />
              </div>
              <div v-if="totalPaid > 0" class="text-xs text-gray-500 text-center">
                Received: <strong class="text-gray-800">LKR {{ lkr(totalPaid) }}</strong>
              </div>
            </div>

            <!-- Amount + Exact + Change on one line (single payment) -->
            <div v-if="!splitPayment" class="px-3 pb-2">
              <div class="flex items-center gap-2">
                <input
                  ref="amountInputRef"
                  v-model.number="form.amount_paid"
                  type="number"
                  min="0"
                  class="flex-1 min-w-0 px-3 py-2.5 rounded-xl border-2 border-gray-200 text-lg font-bold text-center text-gray-900 focus:outline-none focus:border-amber-500"
                  :placeholder="kbShortcutsEnabled ? 'Amount (F3)' : 'Amount received'"
                  @input="amountManuallySet = true; recalc()"
                  @wheel.prevent
                />
                <button
                  @click="amountManuallySet = false; form.amount_paid = total"
                  class="px-3 py-2.5 rounded-xl text-sm font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 border-2 border-amber-300 transition-colors shrink-0"
                >Exact</button>
                <div v-if="changeDue > 0" class="shrink-0 flex flex-col items-center bg-green-50 border-2 border-green-200 rounded-xl px-3 py-1">
                  <span class="text-[10px] font-semibold text-green-600 uppercase tracking-wide leading-none">Change</span>
                  <span class="text-base font-black text-green-600 leading-tight">{{ lkr(changeDue) }}</span>
                </div>
                <div v-else-if="balanceDue > 0.009" class="shrink-0 flex flex-col items-center bg-yellow-50 border-2 border-yellow-200 rounded-xl px-3 py-1">
                  <span class="text-[10px] font-semibold text-yellow-600 uppercase tracking-wide leading-none">Due</span>
                  <span class="text-base font-black text-yellow-600 leading-tight">{{ lkr(balanceDue) }}</span>
                </div>
              </div>
            </div>

            <!-- Error -->
            <p v-if="error" class="mx-3 mb-2 text-sm text-red-700 bg-red-50 border border-red-200 px-3 py-2 rounded-xl flex items-center gap-2">
              <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
            </p>

        </div>

        <!-- ── Action buttons (always pinned at bottom) ── -->
        <div class="shrink-0 flex gap-2 px-3 pb-3 pt-2 bg-white border-t border-gray-200">
          <button
            @click="submit('draft')"
            :disabled="saving || !form.items.filter(i => i.product_id).length"
            class="flex items-center justify-center gap-2 px-4 py-3.5 bg-gray-600 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-bold text-sm transition-colors shrink-0"
          >
            <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
            <span v-else>📋</span>
            Draft
            <span v-if="kbShortcutsEnabled" class="opacity-50 text-xs font-normal">F9</span>
          </button>
          <button
            @click="submit('completed')"
            :disabled="saving || !form.items.filter(i => i.product_id).length"
            class="flex-1 flex items-center justify-center gap-2 py-3.5 bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-black text-base shadow-lg shadow-green-500/30 transition-all active:scale-95"
          >
            <ArrowPathIcon v-if="saving" class="w-5 h-5 animate-spin" />
            <span v-else class="text-xl">✓</span>
            {{ saving ? 'Processing…' : 'Complete Sale' }}
            <span v-if="kbShortcutsEnabled" class="opacity-50 text-xs font-normal">F10</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Table picker modal ── -->
    <div v-if="showTablePicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="showTablePicker = false">
      <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Select Table</h3>
            <p class="text-xs text-gray-400 mt-0.5">Tap a table to assign this order</p>
          </div>
          <button @click="showTablePicker = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg text-xl leading-none">✕</button>
        </div>
        <div class="p-5">
          <!-- Walk-in option -->
          <button
            @click="form.table_number = ''; showTablePicker = false"
            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 mb-4 transition-all font-semibold text-sm"
            :class="!form.table_number ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-200 hover:border-gray-400 text-gray-600'"
          >
            <span class="text-2xl">🚶</span>
            <span>Walk-in / No Table</span>
            <span v-if="!form.table_number" class="ml-auto text-amber-500">✓</span>
          </button>

          <!-- Table grid -->
          <div class="grid grid-cols-4 gap-3 max-h-72 overflow-y-auto">
            <button
              v-for="t in availableTables"
              :key="t.id"
              @click="form.table_number = t.table_number; showTablePicker = false"
              type="button"
              class="flex flex-col items-center justify-center gap-1 aspect-square rounded-2xl border-2 font-bold transition-all"
              :class="form.table_number == t.table_number
                ? 'border-amber-500 bg-amber-500 text-white shadow-lg shadow-amber-200'
                : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-amber-400 hover:bg-amber-50'"
            >
              <span class="text-2xl leading-none">🪑</span>
              <span class="text-sm">{{ t.table_number }}</span>
            </button>
          </div>

          <div v-if="!availableTables.length" class="text-center text-sm text-gray-400 py-6">
            No tables configured. Add tables in Settings.
          </div>
        </div>
      </div>
    </div>

    <!-- Open bottle picker modal -->
    <div v-if="openBottlePicker.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="openBottlePicker.show = false">
      <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <div>
            <h3 class="text-base font-bold text-gray-900">Select Opened Bottle</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ openBottlePicker.item?.product_ref?.name }}</p>
          </div>
          <button @click="openBottlePicker.show = false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">✕</button>
        </div>
        <div class="p-4">
          <div v-if="openBottlePicker.loading" class="flex items-center justify-center py-10 text-gray-400">
            <ArrowPathIcon class="w-5 h-5 animate-spin mr-2" /> Loading…
          </div>
          <div v-else-if="!openBottlePicker.bottles.length" class="text-center py-10 text-gray-400 text-sm">
            No open bottles available for this product.
          </div>
          <div v-else class="space-y-2 max-h-72 overflow-y-auto">
            <button
              v-for="bottle in openBottlePicker.bottles"
              :key="bottle.id"
              @click="selectOpenBottle(bottle)"
              type="button"
              class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition-colors text-left"
            >
              <div>
                <p class="text-sm font-semibold text-gray-800">Bottle #{{ bottle.id }}</p>
                <p class="text-xs text-gray-400">Opened {{ new Date(bottle.opened_at).toLocaleDateString() }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-bold text-blue-600">{{ bottle.remaining_volume_ml?.toFixed(0) }} ml</p>
                <p class="text-xs text-gray-400">remaining</p>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Keyboard shortcuts help modal -->
    <div v-if="kbShortcutsEnabled && showKbHelp" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4" @click.self="showKbHelp = false">
      <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <QuestionMarkCircleIcon class="w-5 h-5 text-amber-500" />
            <h3 class="text-base font-bold text-gray-900">Keyboard Shortcuts</h3>
          </div>
          <button @click="showKbHelp = false" class="text-gray-400 hover:text-gray-700 text-xl leading-none">✕</button>
        </div>
        <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
          <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Navigation</div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Focus product search</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">F1</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Focus barcode input</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">F2</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Focus amount paid</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">F3</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Close / blur</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Esc</kbd>
          </div>
          <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 mt-3">Actions</div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Save draft</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">F9</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Complete sale</span>
            <kbd class="px-2 py-0.5 rounded bg-amber-100 border border-amber-300 text-xs font-mono font-bold text-amber-700">F10</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Increment last item qty</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">+</kbd>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Decrement last item qty</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">−</kbd>
          </div>
        </div>
        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
          Alt+C/D/S/E/N for payment shortcuts.
        </div>
      </div>
    </div>

    <!-- Camera scanner overlay -->
    <div v-if="scannerOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
      <div class="w-full max-w-lg bg-gray-900 text-white rounded-2xl overflow-hidden shadow-2xl border border-gray-700">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
          <div>
            <h3 class="text-lg font-semibold">Camera Scanner</h3>
            <p class="text-xs text-gray-400 mt-0.5">Point camera at a product barcode</p>
          </div>
          <button type="button" @click="closeScanner" class="text-gray-400 hover:text-white text-2xl leading-none">✕</button>
        </div>
        <div class="p-5 space-y-4">
          <div class="rounded-2xl overflow-hidden bg-black border border-gray-700 relative">
            <video ref="scannerVideo" autoplay playsinline muted class="w-full h-[280px] object-cover"></video>
            <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
              <p class="text-xs text-gray-300">Detected: <span class="font-semibold text-amber-300">{{ scannerDetected || 'waiting…' }}</span></p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">{{ scannerStatus }}</span>
            <button @click="closeScanner" class="px-4 py-2 rounded-lg border border-gray-700 text-gray-200 hover:bg-gray-800 text-sm">Close</button>
          </div>
          <div v-if="scannerError" class="text-sm text-red-300 bg-red-950/60 border border-red-800 rounded-lg px-3 py-2">{{ scannerError }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import {
  ArrowLeftIcon, PlusIcon, XMarkIcon,
  ShoppingCartIcon, CheckCircleIcon, ArrowPathIcon,
  ExclamationTriangleIcon, QrCodeIcon, MagnifyingGlassIcon, ShoppingBagIcon,
  QuestionMarkCircleIcon, TableCellsIcon, ChevronDownIcon, PrinterIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route  = useRoute()

// ── Data ──────────────────────────────────────────────
const products        = ref([])
const customers       = ref([])
const availableTables = ref([])
const taxes           = ref([])
const draftBills      = ref([])
const loadingDraft    = ref(false)
const activeDraftId   = ref(null)

const activeCategory    = ref('All')
const productGridSearch = ref('')
const gridFocusIndex    = ref(-1)

const form = reactive({
  customer_id: '', payment_method: 'cash', payment_status: 'paid',
  discount: 0, tax: 0, tax_rate: 0, amount_paid: 0, notes: '',
  table_number: '', status: 'completed', card_reference: '',
  items: [],
})
const selectedTaxId  = ref('')
const discountType   = ref('fixed') // 'fixed' | 'percent'
const discountInput  = ref(0)

const splitPayment        = ref(false)
const splitCash           = ref(0)
const splitCard           = ref(0)
const splitCardReference  = ref('')
const amountManuallySet   = ref(false)

const searchInputRef   = ref(null)
const barcodeInputRef  = ref(null)
const amountInputRef   = ref(null)
const showKbHelp       = ref(false)
const kbShortcutsEnabled = ref(localStorage.getItem('pos_keyboard_shortcuts') !== 'false')
const lastReceiptId = ref(localStorage.getItem('pos_last_receipt_id') || '')

const saving              = ref(false)
const error               = ref('')
const showNewCustomer     = ref(false)
const savingCustomer      = ref(false)
const newCustomerError    = ref('')
const newCustomer         = reactive({ name: '', phone: '', email: '' })
const showPricingDetails  = ref(false)
const showTablePicker     = ref(false)
const showProductPanel    = ref(true)

const barcodeInput = ref('')
const barcodeError = ref('')
let barcodeClearTimer = null

const openBottlePicker = reactive({ show: false, item: null, bottles: [], loading: false })

// Kitchen notes quick buttons
const kitchenNotes = ['No Ice', 'Less Sugar', 'Extra Spicy', 'No Onion', 'Well Done', 'Medium', 'No Garnish']

function toggleKitchenNote(item, note) {
  const current = item.item_notes || ''
  if (current.includes(note)) {
    item.item_notes = current.replace(note, '').replace(/,\s*,/g, ',').replace(/^,\s*|,\s*$/g, '').trim()
  } else {
    item.item_notes = current ? `${current}, ${note}` : note
  }
}

// Category colors and emojis
const categoryColorMap = [
  'bg-red-500', 'bg-blue-500', 'bg-emerald-500', 'bg-violet-500',
  'bg-orange-500', 'bg-teal-500', 'bg-pink-500', 'bg-indigo-500',
  'bg-rose-500', 'bg-cyan-500', 'bg-lime-500', 'bg-fuchsia-500',
]

function getCategoryActiveBg(cat, idx) {
  if (cat === 'All') return 'bg-amber-500'
  return categoryColorMap[idx % categoryColorMap.length] || 'bg-amber-500'
}

function getCategoryEmoji(name) {
  const lower = (name || '').toLowerCase()
  if (lower === 'all') return '🏪'
  if (lower.includes('liquor') || lower.includes('beer') || lower.includes('alcohol')) return '🍺'
  if (lower.includes('whisky') || lower.includes('whiskey') || lower.includes('bourbon')) return '🥃'
  if (lower.includes('wine')) return '🍷'
  if (lower.includes('vodka') || lower.includes('gin') || lower.includes('rum')) return '🍸'
  if (lower.includes('food') || lower.includes('meal') || lower.includes('main')) return '🍽️'
  if (lower.includes('snack') || lower.includes('starter') || lower.includes('appetizer')) return '🍟'
  if (lower.includes('dessert') || lower.includes('cake') || lower.includes('sweet')) return '🍰'
  if (lower.includes('coffee') || lower.includes('espresso')) return '☕'
  if (lower.includes('tea')) return '🍵'
  if (lower.includes('juice') || lower.includes('soft') || lower.includes('soda')) return '🥤'
  if (lower.includes('water')) return '💧'
  if (lower.includes('rice') || lower.includes('pasta') || lower.includes('noodle')) return '🍜'
  if (lower.includes('chicken') || lower.includes('beef') || lower.includes('pork') || lower.includes('meat')) return '🍗'
  if (lower.includes('fish') || lower.includes('seafood') || lower.includes('shrimp')) return '🐟'
  if (lower.includes('veg') || lower.includes('salad')) return '🥗'
  return '📦'
}

// Payment options with icons
const paymentOptions = [
  { value: 'cash', label: 'Cash', icon: '💵' },
  { value: 'card', label: 'Card', icon: '💳' },
]

// ── Open bottle picker ─────────────────────────────────
async function showOpenBottlePicker(item) {
  openBottlePicker.item = item
  openBottlePicker.bottles = []
  openBottlePicker.show = true
  openBottlePicker.loading = true
  try {
    const { data } = await axios.get('/api/open-bottles/available', { params: { product_id: item.product_id } })
    openBottlePicker.bottles = data
  } finally {
    openBottlePicker.loading = false
  }
}

function selectOpenBottle(bottle) {
  const item = openBottlePicker.item
  item.open_bottle_id = bottle.id
  item.open_bottle_ref = bottle
  item.serving_ml = 0
  item.quantity = 1
  const openingVol = bottle.opening_volume_ml || 1
  const remainingVol = bottle.remaining_volume_ml || 0
  const basePrice = Number(item.product_ref?.selling_price || item.unit_price || 0)
  item.unit_price = Math.round((remainingVol / openingVol) * basePrice * 100) / 100
  recalcItem(item)
  openBottlePicker.show = false
}

function clearOpenBottle(item) {
  item.open_bottle_id = null
  item.open_bottle_ref = null
  recalcItem(item)
}

// ── Scanner ────────────────────────────────────────────
const scannerOpen     = ref(false)
const scannerVideo    = ref(null)
const scannerDetected = ref('')
const scannerError    = ref('')
const scannerStatus   = ref('Idle')
const scannerSupported = computed(() => typeof window !== 'undefined' && 'BarcodeDetector' in window)
let scannerStream   = null
let scannerDetector = null
let scannerFrame    = null
let scannerBusy     = false

function toggleSplit() {
  splitPayment.value = !splitPayment.value
  if (splitPayment.value) {
    splitCash.value = total.value
    splitCard.value = 0
  } else {
    form.amount_paid = total.value
  }
}

function onSplitCashInput() {
  const cash = Number(splitCash.value || 0)
  splitCard.value = Math.max(0, Math.round((total.value - cash) * 100) / 100)
}

function onSplitCardInput() {
  const card = Number(splitCard.value || 0)
  splitCash.value = Math.max(0, Math.round((total.value - card) * 100) / 100)
}

// ── Computed ──────────────────────────────────────────
const categoryTabs = computed(() => {
  const cats = new Set()
  for (const p of products.value) {
    if (p.category?.name) cats.add(p.category.name)
  }
  return ['All', ...cats]
})

const gridProducts = computed(() => {
  let list = products.value
  if (activeCategory.value !== 'All') {
    list = list.filter(p => p.category?.name === activeCategory.value)
  }
  const q = productGridSearch.value.trim().toLowerCase()
  if (q) {
    list = list.filter(p =>
      `${p.name || ''} ${p.sku || ''} ${p.barcode || ''} ${p.brand || ''}`.toLowerCase().includes(q)
    )
    list = [...list].sort((a, b) => {
      const aStarts = (a.name || '').toLowerCase().startsWith(q)
      const bStarts = (b.name || '').toLowerCase().startsWith(q)
      if (aStarts && !bStarts) return -1
      if (!aStarts && bStarts) return 1
      return 0
    })
  }
  return list
})

watch(gridProducts, () => { gridFocusIndex.value = -1 })

const subtotal   = computed(() => form.items.reduce((s, i) => s + (i._lineTotal || 0), 0))
const total      = computed(() => Math.max(0, subtotal.value - (form.discount || 0) + (form.tax || 0)))
const totalPaid  = computed(() => splitPayment.value
  ? (Number(splitCash.value || 0) + Number(splitCard.value || 0))
  : Number(form.amount_paid || 0))
const balanceDue = computed(() => Math.max(0, total.value - totalPaid.value))
const changeDue  = computed(() => Math.max(0, totalPaid.value - total.value))

const quickDenominations = computed(() => {
  const t = total.value
  if (t <= 0) return [100, 500, 1000, 2000]
  const roundedUp = Math.ceil(t / 100) * 100
  const standard = [100, 500, 1000, 2000, 5000, 10000]
  const above = standard.filter(d => d > roundedUp).slice(0, 3)
  return [...new Set([roundedUp, ...above])].sort((a, b) => a - b).slice(0, 4)
})

// ── Bill helpers ───────────────────────────────────────
function isInBill(productId) {
  return form.items.some(i => i.product_id == productId)
}

function getBillQty(productId) {
  return form.items.filter(i => i.product_id == productId).reduce((sum, i) => sum + (i.quantity || 0), 0)
}

const LIQUOR_TYPES = ['liquor', 'whisky', 'vodka']

function isStockTracked(product) {
  return String(product?.product_type ?? '').toLowerCase() !== 'food'
}

function addProductFromGrid(product) {
  if (isStockTracked(product) && product.stock_quantity < 1 && !(product.open_bottles_remaining_ml > 0)) return
  const isLiquor = LIQUOR_TYPES.includes(String(product?.product_type ?? '').toLowerCase())
    || ['Hard Liquor', 'Foreign Liquor'].includes(product?.category?.name)
  if (!isLiquor) {
    const existing = form.items.find(i => i.product_id == product.id)
    if (existing) {
      existing.quantity++
      recalcItem(existing)
      return
    }
  }
  const item = newItem()
  item.product_id            = product.id
  item.product_ref           = product
  item.product_search        = product.name || ''
  item.unit_price            = product.selling_price
  item.bottle_deposit_amount = Number(product.bottle_deposit_amount || 0)
  form.items.push(item)
  recalcItem(item)
}

function decrementItem(item, index) {
  if (item.quantity > 1) {
    item.quantity--
    recalcItem(item)
  } else {
    removeItem(index)
  }
}

function newItem() {
  return {
    product_id: '', product_search: '', quantity: 1, unit_price: 0, discount: 0,
    empty_bottle_returned: false, bottle_deposit_amount: 0, serving_ml: 0,
    open_bottle_id: null, open_bottle_ref: null,
    product_ref: null, _lineTotal: 0,
    item_notes: '', item_notes_custom: '', selected_shot_variant: null,
  }
}

function addItem()      { form.items.push(newItem()) }
function removeItem(i)  { form.items.splice(i, 1); recalc() }

function recalcItem(item) {
  const effectiveUnitPrice = getEffectiveUnitPrice(item)
  const depositAdd = item.product_ref?.bottle_deposit_required && !item.empty_bottle_returned
    ? Number(item.bottle_deposit_amount || 0) * Number(item.quantity || 0)
    : 0
  item._lineTotal = (effectiveUnitPrice * item.quantity) - (item.discount || 0) + depositAdd
  recalc()
}

function getEffectiveUnitPrice(item) {
  if (item.open_bottle_id) return Number(item.unit_price || 0)
  if (item.selected_shot_variant) return Number(item.selected_shot_variant.price || 0)
  const baseUnitPrice = Number(item.unit_price || 0)
  const servingMl = Number(item.serving_ml || 0)
  const isLiquor = String(item.product_ref?.product_type || '').toLowerCase() === 'liquor'
    || ['Hard Liquor', 'Foreign Liquor'].includes(item.product_ref?.category?.name)
  if (!isLiquor || servingMl <= 0) return baseUnitPrice
  const baseMl = parseBaseUnitMl(item.product_ref?.base_unit)
  if (baseMl <= 0) return baseUnitPrice
  return Math.round(((baseUnitPrice / baseMl) * servingMl) * 100) / 100
}

function parseBaseUnitMl(baseUnit) {
  const text = String(baseUnit || '').toLowerCase()
  const mlMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*ml/)
  if (mlMatch) return Number(mlMatch[1]) || 0
  const lMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*l\b/)
  if (lMatch) return (Number(lMatch[1]) || 0) * 1000
  return 0
}

function applyDiscount() {
  const v = Number(discountInput.value || 0)
  form.discount = discountType.value === 'percent'
    ? Math.round(subtotal.value * (v / 100) * 100) / 100
    : v
  recalc()
}

function toggleDiscountType() {
  discountType.value = discountType.value === 'fixed' ? 'percent' : 'fixed'
  discountInput.value = 0
  form.discount = 0
  recalc()
}

function recalc() {
  if (discountType.value === 'percent') {
    form.discount = Math.round(subtotal.value * (Number(discountInput.value || 0) / 100) * 100) / 100
  }
  if (form.tax_rate > 0) {
    form.tax = Math.round(subtotal.value * (form.tax_rate / 100) * 100) / 100
  }
  if (!amountManuallySet.value) {
    form.amount_paid = total.value
  }
}

function applyTax() {
  const t = taxes.value.find(x => x.id == selectedTaxId.value)
  if (t) { form.tax_rate = t.rate; recalc() }
  else   { form.tax_rate = 0; form.tax = 0 }
}

function fillProduct(item) {
  const p = products.value.find(x => x.id == item.product_id)
  if (!p) { item.product_ref = null; return }
  item.product_ref    = p
  item.product_search = p.name || ''
  item.unit_price     = p.selling_price
  item.serving_ml            = 0
  item.selected_shot_variant = null
  item.empty_bottle_returned = false
  item.bottle_deposit_amount = Number(p.bottle_deposit_amount || 0)
  recalcItem(item)
}

function resetForm() {
  activeDraftId.value    = null
  form.customer_id       = ''
  form.payment_method    = 'cash'
  form.payment_status    = 'paid'
  form.discount          = 0
  form.tax               = 0
  form.tax_rate          = 0
  form.amount_paid       = 0
  form.notes             = ''
  form.table_number      = ''
  form.status            = 'completed'
  form.card_reference    = ''
  form.items             = []
  splitPayment.value        = false
  splitCash.value           = 0
  splitCard.value           = 0
  splitCardReference.value  = ''
  amountManuallySet.value   = false
  selectedTaxId.value       = ''
  discountType.value        = 'fixed'
  discountInput.value       = 0
  error.value               = ''
  showPricingDetails.value  = false
  nextTick(() => barcodeInputRef.value?.focus())
}

// ── Draft loading ──────────────────────────────────────
async function loadDraft(draft) {
  loadingDraft.value = true
  try {
    const { data } = await axios.get(`/api/sales/${draft.id}`)
    activeDraftId.value   = data.id
    form.customer_id      = data.customer_id || ''
    form.payment_method   = data.payment_method || 'cash'
    form.payment_status   = 'paid'
    form.discount         = data.discount || 0
    discountType.value    = 'fixed'
    discountInput.value   = data.discount || 0
    form.tax              = data.tax || 0
    form.tax_rate         = data.tax_rate || 0
    form.amount_paid      = 0
    form.notes            = data.notes || ''
    form.table_number     = data.table_number || ''
    form.items = (data.items || []).map(si => {
      const product      = si.product
      const savedPrice   = Number(si.unit_price || 0)
      let   variants     = product?.shot_variants
      if (typeof variants === 'string') { try { variants = JSON.parse(variants) } catch { variants = [] } }
      if (!Array.isArray(variants)) variants = []
      // Match saved unit_price against variant prices (within 0.01 tolerance for float safety)
      const shotVariant  = variants.find(v => Math.abs(Number(v.price) - savedPrice) < 0.01) ?? null
      return {
        product_id:             si.product_id,
        product_search:         product?.name || '',
        quantity:               Number(si.quantity || 0),
        // unit_price is always the bottle price; getEffectiveUnitPrice uses selected_shot_variant.price
        unit_price:             Number(product?.selling_price ?? savedPrice),
        discount:               Number(si.discount || 0),
        serving_ml:             shotVariant ? 0 : Number(si.serving_ml || 0),
        empty_bottle_returned:  false,
        bottle_deposit_amount:  Number(si.product?.bottle_deposit_amount || 0),
        open_bottle_id:         null,
        open_bottle_ref:        null,
        selected_shot_variant:  shotVariant,
        product_ref:            product,
        _lineTotal:             0,
        item_notes:             si.item_notes || '',
        item_notes_custom:      '',
      }
    })
    form.items.forEach(recalcItem)
  } finally {
    loadingDraft.value = false
  }
}

// ── Customer creation ──────────────────────────────────
async function saveNewCustomer() {
  if (!newCustomer.name.trim()) return
  savingCustomer.value = true; newCustomerError.value = ''
  try {
    const { data } = await axios.post('/api/customers', {
      name:  newCustomer.name.trim(),
      phone: newCustomer.phone.trim() || null,
      email: newCustomer.email.trim() || null,
    })
    customers.value.unshift(data)
    form.customer_id = data.id
    showNewCustomer.value = false
    newCustomer.name = ''; newCustomer.phone = ''; newCustomer.email = ''
  } catch (e) {
    newCustomerError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Could not save customer'
  } finally { savingCustomer.value = false }
}

// ── Barcode scanner ────────────────────────────────────
function processBarcode(code) {
  const normalized = (code ?? '').trim()
  barcodeInput.value = ''
  if (!normalized) return
  const product = products.value.find(p =>
    p.barcode?.toLowerCase() === normalized.toLowerCase() ||
    p.sku?.toLowerCase() === normalized.toLowerCase()
  )
  if (!product) {
    barcodeError.value = `"${normalized}" not found`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    nextTick(() => barcodeInputRef.value?.focus())
    return
  }
  if (isStockTracked(product) && product.stock_quantity < 1) {
    barcodeError.value = `"${product.name}" is out of stock`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    nextTick(() => barcodeInputRef.value?.focus())
    return
  }
  addProductFromGrid(product)
  barcodeError.value = ''
  nextTick(() => barcodeInputRef.value?.focus())
}

function maxShotsFromOpenBottle(item) {
  const remaining = item.product_ref?.open_bottles_remaining_ml
  const ml = item.serving_ml || (item.selected_shot_variant ? Number(item.selected_shot_variant.name) : 0)
  if (!remaining || !ml || item.product_ref?.stock_quantity > 0) return null
  return Math.floor(remaining / ml)
}

function selectShotVariant(item, variant) {
  const toggled = item.selected_shot_variant?.name === variant.name ? null : variant
  item.selected_shot_variant = toggled
  item.serving_ml = toggled ? (Number(toggled.name) || 0) : 0
  recalcItem(item)
}

function onSearchInput() {
  if (productGridSearch.value) activeCategory.value = 'All'
}

function scanBarcode() { processBarcode(barcodeInput.value) }

async function openScanner() {
  scannerError.value = ''; scannerDetected.value = ''; scannerOpen.value = true
  await nextTick()
  if (!scannerSupported.value) { scannerStatus.value = 'Camera detection not supported in this browser.'; return }
  try {
    scannerStatus.value = 'Starting camera…'
    scannerDetector = new window.BarcodeDetector({ formats: ['code_128', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code'] })
    scannerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
    if (scannerVideo.value) { scannerVideo.value.srcObject = scannerStream; await scannerVideo.value.play() }
    scannerStatus.value = 'Scanning…'
    scannerLoop()
  } catch (e) {
    scannerError.value = e?.message ?? 'Could not start camera.'
    scannerStatus.value = 'Scanner unavailable.'
  }
}

async function scannerLoop() {
  if (!scannerOpen.value || !scannerDetector || !scannerVideo.value || scannerBusy) return
  scannerFrame = requestAnimationFrame(scannerLoop)
  try {
    scannerBusy = true
    const codes = await scannerDetector.detect(scannerVideo.value)
    if (codes?.length) {
      const value = codes[0].rawValue || ''
      if (value) { scannerDetected.value = value; processBarcode(value); closeScanner() }
    }
  } catch (e) {
    scannerError.value = e?.message ?? 'Detection failed.'
  } finally { scannerBusy = false }
}

function closeScanner() {
  scannerOpen.value = false; scannerStatus.value = 'Idle'
  if (scannerFrame) cancelAnimationFrame(scannerFrame)
  scannerFrame = null; scannerBusy = false
  scannerDetected.value = ''; scannerError.value = ''
  if (scannerStream) { scannerStream.getTracks().forEach(t => t.stop()); scannerStream = null }
}

// ── Submit ─────────────────────────────────────────────
async function submit(billStatus) {
  saving.value = true; error.value = ''
  try {
    const isSplit = splitPayment.value && billStatus !== 'draft'
    const splitPayments = isSplit ? [
      { payment_method: 'cash', amount: Number(splitCash.value || 0) },
      { payment_method: 'card', amount: Number(splitCard.value || 0), card_reference: splitCardReference.value || null },
    ].filter(p => p.amount > 0) : null

    const payload = {
      customer_id:    form.customer_id || null,
      payment_method: isSplit ? 'other' : form.payment_method,
      payment_status: billStatus === 'draft' ? 'pending' : form.payment_status,
      discount:       form.discount,
      tax:            form.tax,
      tax_rate:       form.tax_rate,
      amount_paid:    billStatus === 'draft' ? 0 : (isSplit ? totalPaid.value : Number(form.amount_paid || 0)),
      status:         billStatus,
      table_number:   form.table_number || null,
      notes:          form.notes,
      card_reference: form.payment_method === 'card' && !isSplit ? (form.card_reference || null) : null,
      ...(splitPayments ? { payments: splitPayments } : {}),
      items: form.items.filter(i => i.product_id).map(i => ({
        product_id:            i.product_id,
        quantity:              i.quantity,
        unit_price:            getEffectiveUnitPrice(i),
        discount:              i.discount,
        empty_bottle_returned: i.empty_bottle_returned,
        bottle_deposit_amount: i.bottle_deposit_amount,
        serving_ml:            i.selected_shot_variant ? (Number(i.selected_shot_variant.name) || i.serving_ml) : i.serving_ml,
        open_bottle_id:        i.open_bottle_id || null,
        item_notes:            [i.item_notes, i.item_notes_custom].filter(Boolean).join(', ') || null,
      })),
    }

    let saleId
    if (activeDraftId.value) {
      const { data } = await axios.put(`/api/sales/${activeDraftId.value}`, payload)
      saleId = data.id ?? activeDraftId.value
    } else {
      const { data } = await axios.post('/api/sales', payload)
      saleId = data.id
    }

    if (billStatus === 'completed') {
      lastReceiptId.value = String(saleId)
      localStorage.setItem('pos_last_receipt_id', String(saleId))
      router.push(`/sales/${saleId}?print=1`)
    } else {
      router.push('/sales')
    }
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'An error occurred. Please try again.'
  } finally { saving.value = false }
}

// ── Keyboard shortcuts ──────────────────────────────────
function isTypingInInput() {
  const tag = document.activeElement?.tagName?.toLowerCase()
  return ['input', 'textarea', 'select'].includes(tag)
}

function handleKeydown(e) {
  if (!kbShortcutsEnabled.value) return
  if (e.key === 'F1') { e.preventDefault(); searchInputRef.value?.focus(); searchInputRef.value?.select(); return }
  if (e.key === 'F2') { e.preventDefault(); barcodeInputRef.value?.focus(); barcodeInputRef.value?.select(); return }
  if (e.key === 'F3') { e.preventDefault(); amountInputRef.value?.focus(); amountInputRef.value?.select(); return }
  if (e.key === 'F9') { e.preventDefault(); if (!saving.value && form.items.some(i => i.product_id)) submit('draft'); return }
  if (e.key === 'F10') { e.preventDefault(); if (!saving.value && form.items.some(i => i.product_id)) submit('completed'); return }
  if (e.key === 'Escape') {
    if (showTablePicker.value)      { showTablePicker.value = false; return }
    if (openBottlePicker.show) { openBottlePicker.show = false; return }
    if (scannerOpen.value)     { closeScanner(); return }
    if (showKbHelp.value)      { showKbHelp.value = false; return }
    document.activeElement?.blur()
    return
  }
  if (e.altKey) {
    switch (e.key.toLowerCase()) {
      case 'c': e.preventDefault(); form.payment_method = 'cash'; splitPayment.value = false; break
      case 'd': e.preventDefault(); form.payment_method = 'card'; splitPayment.value = false; break
      case 's': e.preventDefault(); toggleSplit(); break
      case 'e': e.preventDefault()
        if (splitPayment.value) { splitCash.value = total.value; splitCard.value = 0 }
        else { form.amount_paid = total.value }
        break
      case 'n': e.preventDefault(); resetForm(); break
    }
    return
  }
  // Allow arrow keys from the product search input to enter grid navigation
  const fromSearch = document.activeElement === searchInputRef.value
  if (fromSearch && ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(e.key)) {
    // fall through to grid nav below
  } else if (isTypingInInput()) return

  // ── Product grid arrow key navigation ──
  const gridLen = gridProducts.value.length
  if (gridLen > 0 && ['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Enter'].includes(e.key)) {
    const cols = window.innerWidth >= 1536 ? 4 : window.innerWidth >= 1280 ? 3 : 2
    if (e.key === 'ArrowRight') {
      e.preventDefault()
      gridFocusIndex.value = gridFocusIndex.value < gridLen - 1 ? gridFocusIndex.value + 1 : 0
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault()
      gridFocusIndex.value = gridFocusIndex.value > 0 ? gridFocusIndex.value - 1 : gridLen - 1
    } else if (e.key === 'ArrowDown') {
      e.preventDefault()
      if (gridFocusIndex.value < 0) {
        gridFocusIndex.value = 0
      } else {
        const next = gridFocusIndex.value + cols
        gridFocusIndex.value = next < gridLen ? next : gridFocusIndex.value
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      if (gridFocusIndex.value < 0) {
        gridFocusIndex.value = 0
      } else {
        const prev = gridFocusIndex.value - cols
        gridFocusIndex.value = prev >= 0 ? prev : gridFocusIndex.value
      }
    } else if (e.key === 'Enter' && gridFocusIndex.value >= 0) {
      e.preventDefault()
      addProductFromGrid(gridProducts.value[gridFocusIndex.value])
    }
    if (e.key !== 'Enter') {
      nextTick(() => {
        document.querySelector(`[data-grid-idx="${gridFocusIndex.value}"]`)?.scrollIntoView({ block: 'nearest' })
      })
    }
    return
  }

  if (e.key === '?') { e.preventDefault(); showKbHelp.value = !showKbHelp.value; return }
  if (e.key === '+' || e.key === '=' || e.key === 'Add') {
    e.preventDefault()
    const last = [...form.items].reverse().find(i => i.product_id)
    if (last && !last.open_bottle_id) { last.quantity++; recalcItem(last) }
    return
  }
  if (e.key === '-' || e.key === 'Subtract') {
    e.preventDefault()
    const last = [...form.items].reverse().find(i => i.product_id)
    if (last && !last.open_bottle_id) decrementItem(last, form.items.lastIndexOf(last))
    return
  }
}

// ── Utilities ──────────────────────────────────────────
function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(async () => {
  const [p, c, t, tb, drafts] = await Promise.all([
    axios.get('/api/products', { params: { per_page: 1000 } }),
    axios.get('/api/customers/all'),
    axios.get('/api/tax-settings'),
    axios.get('/api/tables/all'),
    axios.get('/api/sales', { params: { status: 'draft', per_page: 50 } }),
  ])
  products.value        = p.data.data
  customers.value       = c.data
  taxes.value           = t.data.filter(x => x.is_active)
  availableTables.value = tb.data
  draftBills.value      = drafts.data.data

  const hasLiquor = products.value.some(p => p.category?.name === 'Liquor')
  if (hasLiquor) activeCategory.value = 'Liquor'

  const draftId = route.query.draft
  if (draftId) {
    const draft = draftBills.value.find(d => d.id == draftId)
    if (draft) {
      await loadDraft(draft)
    } else {
      await loadDraft({ id: draftId })
    }
  }

  kbShortcutsEnabled.value = localStorage.getItem('pos_keyboard_shortcuts') !== 'false'
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  closeScanner()
  clearTimeout(barcodeClearTimer)
  document.removeEventListener('keydown', handleKeydown)
})
</script>
