<template>
  <div class="flex flex-col bg-gray-100 overflow-hidden" style="height: calc(100vh - 60px)">

    <!-- Top bar -->
    <div class="flex items-center gap-2 px-4 py-2 bg-white border-b border-gray-200 shrink-0 flex-wrap">
      <router-link to="/sales" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
        <ArrowLeftIcon class="w-4 h-4" /> Bills
      </router-link>
      <span class="text-gray-300 shrink-0">/</span>
      <h2 class="text-sm font-semibold text-gray-800 shrink-0">New Bill</h2>
      <button v-if="kbShortcutsEnabled" @click="showKbHelp = !showKbHelp" type="button" title="Keyboard shortcuts (?)"
        class="ml-auto shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
        <QuestionMarkCircleIcon class="w-4 h-4" /><span class="hidden sm:inline">Shortcuts</span>
      </button>

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

    <!-- Main 2-panel layout -->
    <div class="flex flex-1 overflow-hidden">

      <!-- ── LEFT: Product browser ── -->
      <div class="flex flex-col w-[56%] xl:w-[60%] overflow-hidden bg-white border-r border-gray-200">

        <!-- Search + barcode -->
        <div class="flex gap-2 px-3 py-2 border-b border-gray-100 shrink-0">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input
              ref="searchInputRef"
              v-model="productGridSearch"
              type="text"
              :placeholder="kbShortcutsEnabled ? 'Search products… (F1)' : 'Search products…'"
              class="form-input pl-8 text-sm py-2"
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
              class="form-input pl-8 text-sm py-2 w-32"
              @keyup.enter="scanBarcode"
            />
          </div>
          <button type="button" @click="openScanner" title="Camera scanner" class="inline-flex items-center justify-center w-10 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 shrink-0">
            <QrCodeIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Category tabs -->
        <div class="flex gap-1.5 px-3 py-2 overflow-x-auto border-b border-gray-100 shrink-0">
          <button
            v-for="cat in categoryTabs"
            :key="cat"
            @click="activeCategory = cat"
            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition-colors"
            :class="activeCategory === cat
              ? 'bg-amber-500 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ cat }}
          </button>
        </div>

        <!-- Barcode error -->
        <p v-if="barcodeError" class="mx-3 mt-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-1.5 flex items-center gap-1.5 shrink-0">
          <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ barcodeError }}
        </p>

        <!-- Product grid -->
        <div class="flex-1 overflow-y-auto p-3">
          <div v-if="!gridProducts.length" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <ShoppingBagIcon class="w-12 h-12 opacity-20 mb-2" />
            <p class="text-sm">No products found</p>
          </div>
          <div class="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2">
            <button
              v-for="(product, idx) in gridProducts"
              :key="product.id"
              :data-grid-idx="idx"
              @click="addProductFromGrid(product)"
              :disabled="isStockTracked(product) && product.stock_quantity < 1"
              type="button"
              class="relative flex flex-col items-start p-2.5 rounded-xl border-2 text-left transition-all select-none"
              :class="isStockTracked(product) && product.stock_quantity < 1
                ? 'border-gray-100 bg-gray-50 opacity-40 cursor-not-allowed'
                : gridFocusIndex === idx
                  ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-400 shadow-md'
                  : isInBill(product.id)
                    ? 'border-amber-400 bg-amber-50 hover:bg-amber-100 shadow-sm'
                    : 'border-gray-200 bg-white hover:border-amber-300 hover:bg-amber-50 hover:shadow-sm active:scale-95'"
            >
              <!-- In-bill qty badge -->
              <span
                v-if="isInBill(product.id)"
                class="absolute top-1.5 right-1.5 w-5 h-5 bg-amber-500 text-white rounded-full text-xs font-bold flex items-center justify-center shadow"
              >{{ getBillQty(product.id) }}</span>

              <!-- Image -->
              <div class="w-full aspect-square rounded-lg overflow-hidden bg-gray-100 mb-2 shrink-0">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <ShoppingBagIcon class="w-6 h-6 text-gray-300" />
                </div>
              </div>

              <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 mb-1 w-full">{{ product.name }}</p>
              <p class="text-xs font-bold text-amber-600">LKR {{ lkr(product.selling_price) }}</p>
              <p v-if="isStockTracked(product)" class="text-xs text-gray-400 mt-0.5">
                <span :class="product.stock_quantity <= product.min_stock_level ? 'text-red-400' : ''">
                  {{ product.stock_quantity }} left
                </span>
              </p>
            </button>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Bill + Payment ── -->
      <div class="flex flex-col w-[44%] xl:w-[40%] overflow-hidden">

        <!-- Table + Customer -->
        <div class="px-3 py-2.5 bg-white border-b border-gray-200 shrink-0 space-y-2">
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Table</label>
              <select v-model="form.table_number" class="form-input text-sm py-1.5">
                <option value="">Walk-in</option>
                <option v-for="t in availableTables" :key="t.id" :value="t.table_number">
                  {{ t.table_number }}
                </option>
              </select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Customer</label>
              <div class="flex gap-1">
                <select v-model="form.customer_id" class="form-input text-sm py-1.5 flex-1 min-w-0">
                  <option value="">Walk-in</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <button
                  @click="showNewCustomer = !showNewCustomer"
                  type="button"
                  class="w-8 shrink-0 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50"
                  title="Add new customer"
                >
                  <PlusIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Quick new customer -->
          <div v-if="showNewCustomer" class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 space-y-1.5">
            <div class="grid grid-cols-2 gap-1.5">
              <input v-model="newCustomer.name" type="text" placeholder="Name *" class="form-input text-xs py-1.5" @keyup.enter="saveNewCustomer" />
              <input v-model="newCustomer.phone" type="tel" placeholder="Phone" class="form-input text-xs py-1.5" @keyup.enter="saveNewCustomer" />
            </div>
            <p v-if="newCustomerError" class="text-xs text-red-600">{{ newCustomerError }}</p>
            <button @click="saveNewCustomer" :disabled="savingCustomer || !newCustomer.name.trim()" class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-xs font-semibold">
              {{ savingCustomer ? 'Saving…' : 'Save & Select' }}
            </button>
          </div>

          <input v-model="form.notes" type="text" placeholder="Order notes…" class="form-input text-sm py-1.5" />
        </div>

        <!-- Bill items (scrollable) -->
        <div class="flex-1 overflow-y-auto bg-white">
          <div v-if="form.items.some(i => i.product_id)" class="flex justify-end px-3 py-1.5 border-b border-gray-100">
            <button @click="clearCart" type="button"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors border border-red-200">
              <TrashIcon class="w-3.5 h-3.5" />
              Clear Cart <kbd class="ml-1 text-[10px] opacity-60">Del</kbd>
            </button>
          </div>
          <div v-if="!form.items.length" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <ShoppingCartIcon class="w-10 h-10 opacity-20 mb-2" />
            <p class="text-sm">Tap products to add</p>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="(item, i) in form.items"
              :key="i"
              class="px-3 py-2.5 hover:bg-gray-50 transition-colors"
            >
              <!-- If no product selected yet (manual line) -->
              <div v-if="!item.product_id" class="space-y-1.5">
                <select v-model="item.product_id" class="form-input text-sm" @change="fillProduct(item)">
                  <option value="">— Select product —</option>
                  <option v-for="p in products" :key="p.id" :value="p.id" :disabled="isStockTracked(p) && p.stock_quantity < 1">
                    {{ p.name }} {{ isStockTracked(p) ? '(' + p.stock_quantity + ' in stock)' : '' }}
                  </option>
                </select>
                <button @click="removeItem(i)" class="text-xs text-red-400 hover:text-red-600">Remove</button>
              </div>

              <!-- Product line -->
              <template v-else>
                <div class="flex items-start gap-2">
                  <!-- Thumbnail -->
                  <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                    <img v-if="item.product_ref?.image" :src="item.product_ref.image" :alt="item.product_ref?.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <ShoppingBagIcon class="w-5 h-5 text-gray-300" />
                    </div>
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                      <p class="text-sm font-semibold text-gray-800 leading-tight truncate">
                        {{ item.product_ref?.name || 'Product' }}
                      </p>
                      <span v-if="item.open_bottle_id" class="shrink-0 px-1.5 py-0 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">OPENED</span>
                    </div>
                    <p class="text-xs text-amber-600">
                      LKR {{ lkr(getEffectiveUnitPrice(item)) }}
                      <span v-if="item.serving_ml > 0 && !item.open_bottle_id" class="text-gray-400"> · {{ item.serving_ml }}ml</span>
                      <span v-if="!item.empty_bottle_returned && item.product_ref?.bottle_deposit_required" class="text-gray-400"> · +dep</span>
                    </p>
                  </div>

                  <!-- Qty control -->
                  <div class="flex items-center gap-1 shrink-0">
                    <button
                      @click="decrementItem(item, i)"
                      :disabled="!!item.open_bottle_id"
                      class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center text-gray-600 font-bold text-lg leading-none transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                    >−</button>
                    <span class="w-6 text-center text-sm font-bold text-gray-900">{{ item.quantity }}</span>
                    <button
                      @click="item.quantity++; recalcItem(item)"
                      :disabled="!!item.open_bottle_id"
                      class="w-7 h-7 rounded-full bg-amber-100 hover:bg-amber-200 flex items-center justify-center text-amber-700 font-bold text-lg leading-none transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                    >+</button>
                  </div>

                  <!-- Line total -->
                  <div class="shrink-0 w-20 text-right">
                    <p class="text-sm font-bold text-gray-900">LKR {{ lkr(item._lineTotal) }}</p>
                  </div>

                  <button @click="removeItem(i)" class="shrink-0 w-5 h-5 flex items-center justify-center text-gray-300 hover:text-red-500 transition-colors">
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>

                <!-- Extras row -->
                <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1.5 items-center">
                  <!-- Item discount -->
                  <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-400">Disc:</span>
                    <input v-model.number="item.discount" type="number" min="0" class="w-16 form-input text-xs py-0.5 text-center" @input="recalcItem(item)" @wheel.prevent placeholder="0" />
                  </div>

                  <!-- Opened bottle badge OR sell opened bottle button -->
                  <template v-if="['Liquor','Whisky','Vodka'].includes(item.product_ref?.product_type) || ['Hard Liquor','Foreign Liquor'].includes(item.product_ref?.category?.name)">
                    <template v-if="item.open_bottle_id">
                      <!-- Show which opened bottle is linked + editable price + clear -->
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                        Opened · {{ item.open_bottle_ref?.remaining_volume_ml?.toFixed(0) }}ml
                      </span>
                      <div class="flex items-center gap-1">
                        <span class="text-xs text-gray-400">Price:</span>
                        <input v-model.number="item.unit_price" type="number" min="0" class="w-20 form-input text-xs py-0.5 text-center" @input="recalcItem(item)" @wheel.prevent />
                      </div>
                      <button @click="clearOpenBottle(item)" type="button" class="text-xs text-red-400 hover:text-red-600">✕ Clear</button>
                    </template>
                    <template v-else>
                      <!-- Serving ml picker -->
                      <div class="flex items-center gap-1">
                        <span class="text-xs text-gray-400">ml:</span>
                        <button v-for="size in ['Hard Liquor','Foreign Liquor'].includes(item.product_ref?.category?.name) ? [25, 50] : [30, 50, 60, 75]" :key="size"
                          @click="item.serving_ml = size; recalcItem(item)" type="button"
                          class="px-1.5 py-0.5 text-xs font-semibold rounded transition-colors"
                          :class="item.serving_ml === size ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-100'"
                        >{{ size }}</button>
                        <input v-model.number="item.serving_ml" type="number" min="0" class="w-14 form-input text-xs py-0.5 text-center" @input="recalcItem(item)" @wheel.prevent placeholder="0" />
                      </div>
                      <!-- Sell opened bottle button -->
                      <button @click="showOpenBottlePicker(item)" type="button"
                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 transition-colors"
                      >Sell opened bottle</button>
                    </template>
                  </template>

                  <!-- Bottle deposit -->
                  <div v-if="item.product_ref?.bottle_deposit_required" class="flex items-center gap-1.5">
                    <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                      <input type="checkbox" v-model="item.empty_bottle_returned" @change="recalcItem(item)" class="rounded text-amber-600" />
                      Bottle returned
                    </label>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Add manual line -->
          <div class="px-3 py-2">
            <button @click="addItem" class="w-full flex items-center justify-center gap-1.5 py-2 rounded-lg border-2 border-dashed border-gray-200 text-gray-400 hover:border-amber-300 hover:text-amber-500 text-xs font-medium transition-colors">
              <PlusIcon class="w-3.5 h-3.5" /> Add line manually
            </button>
          </div>
        </div>

        <!-- ── Totals + Payment (pinned bottom) ── -->
        <div class="shrink-0 border-t border-gray-200 bg-white">

          <!-- Total row (always visible) + collapsible detail -->
          <div class="border-b border-gray-100">
            <button
              @click="showPricingDetails = !showPricingDetails"
              class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <span>TOTAL</span>
                <span v-if="form.discount > 0" class="text-red-400">−{{ lkr(form.discount) }}</span>
                <span v-if="form.tax > 0" class="text-blue-400">+tax {{ form.tax_rate }}%</span>
                <span class="text-gray-300">{{ showPricingDetails ? '▲' : '▼' }}</span>
              </div>
              <span class="text-xl font-bold text-amber-600">LKR {{ lkr(total) }}</span>
            </button>

            <!-- Collapsible: subtotal / discount / tax -->
            <div v-if="showPricingDetails" class="px-3 pb-2 space-y-1.5 border-t border-gray-100 bg-gray-50">
              <div class="flex justify-between text-xs text-gray-500 pt-1.5">
                <span>Subtotal</span><span>LKR {{ lkr(subtotal) }}</span>
              </div>
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs text-gray-500">Discount</span>
                  <button
                    type="button" @click="toggleDiscountType"
                    class="px-1.5 py-0.5 text-xs font-bold rounded border transition-colors shrink-0"
                    :class="discountType === 'percent'
                      ? 'bg-red-500 text-white border-red-500'
                      : 'bg-gray-200 text-gray-600 border-gray-300 hover:bg-gray-300'"
                  >{{ discountType === 'percent' ? '%' : 'LKR' }}</button>
                  <input
                    v-model.number="discountInput"
                    type="number" min="0" :max="discountType === 'percent' ? 100 : undefined"
                    class="w-20 form-input text-xs py-0.5 text-center"
                    @input="applyDiscount" @wheel.prevent placeholder="0"
                  />
                </div>
                <span v-if="form.discount > 0" class="text-xs text-red-500 font-medium">
                  −LKR {{ lkr(form.discount) }}
                  <span v-if="discountType === 'percent'" class="text-gray-400">({{ discountInput }}%)</span>
                </span>
              </div>
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs text-gray-500">Tax</span>
                  <select v-model="selectedTaxId" class="form-input text-xs py-0.5 w-28" @change="applyTax">
                    <option value="">None</option>
                    <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
                  </select>
                </div>
                <span v-if="form.tax > 0" class="text-xs text-blue-500 font-medium">+LKR {{ lkr(form.tax) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment method + status (single row) -->
          <div class="px-3 pt-1.5 pb-1 flex gap-1 flex-wrap items-center">
            <template v-if="!splitPayment">
              <button
                v-for="opt in paymentOptions"
                :key="opt.value"
                @click="form.payment_method = opt.value"
                class="px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
                :class="form.payment_method === opt.value
                  ? 'bg-gray-800 text-white border-gray-800'
                  : 'bg-gray-100 text-gray-500 border-gray-200 hover:border-gray-400'"
              >{{ opt.label }}</button>
            </template>
            <button
              @click="toggleSplit"
              class="px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
              :class="splitPayment
                ? 'bg-purple-600 text-white border-purple-600'
                : 'bg-gray-100 text-gray-500 border-gray-200 hover:border-gray-400'"
            >Split</button>

            <button
              @click="form.payment_status = form.payment_status === 'pending' ? 'paid' : form.payment_status === 'paid' ? 'partial' : 'pending'"
              class="ml-auto px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
              :class="form.payment_status === 'pending'
                ? 'bg-yellow-100 text-yellow-700 border-yellow-300'
                : form.payment_status === 'partial'
                  ? 'bg-orange-100 text-orange-700 border-orange-300'
                  : 'bg-green-100 text-green-700 border-green-300'"
            >{{ form.payment_status === 'pending' ? 'Pending' : form.payment_status === 'partial' ? 'Partial' : 'Paid' }}</button>
          </div>

          <!-- Card reference (single card mode only) -->
          <div v-if="!splitPayment && form.payment_method === 'card'" class="px-3 pb-1">
            <input v-model="form.card_reference" type="text" placeholder="Card receipt reference…" class="form-input text-sm py-1.5 w-full" />
          </div>

          <!-- Split payment inputs -->
          <div v-if="splitPayment" class="px-3 pb-1.5 space-y-1.5">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-xs font-semibold text-gray-500 mb-0.5 block">Cash</label>
                <input v-model.number="splitCash" type="number" min="0" step="1"
                  class="form-input text-base font-bold py-1.5 text-center text-gray-900 w-full"
                  @input="onSplitCashInput" @wheel.prevent />
              </div>
              <div>
                <label class="text-xs font-semibold text-gray-500 mb-0.5 block">Card</label>
                <input v-model.number="splitCard" type="number" min="0" step="1"
                  class="form-input text-base font-bold py-1.5 text-center text-gray-900 w-full"
                  @input="onSplitCardInput" @wheel.prevent />
              </div>
            </div>
            <div class="flex gap-1">
              <button @click="splitCash = total; splitCard = 0"
                class="flex-1 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">
                All Cash
              </button>
              <button @click="splitCard = total; splitCash = 0"
                class="flex-1 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">
                All Card
              </button>
              <button @click="splitCash = Math.ceil(total / 2); splitCard = Math.floor(total / 2)"
                class="flex-1 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors">
                50/50
              </button>
            </div>
            <!-- Card reference when card portion > 0 -->
            <div v-if="splitCard > 0">
              <input v-model="splitCardReference" type="text" placeholder="Card receipt / last 4 digits…" class="form-input text-sm py-1.5 w-full" />
            </div>
            <div v-if="totalPaid > 0" class="flex justify-between text-xs text-gray-500 px-0.5">
              <span>Total received: <strong class="text-gray-800">LKR {{ lkr(totalPaid) }}</strong></span>
            </div>
          </div>

          <!-- Amount + quick cash (single payment mode) -->
          <div v-if="!splitPayment" class="px-3 pb-1.5">
            <div class="flex gap-1.5">
              <input
                ref="amountInputRef"
                v-model.number="form.amount_paid"
                type="number"
                min="0"
                class="form-input text-base font-bold py-1.5 text-center text-gray-900 flex-1"
                :placeholder="kbShortcutsEnabled ? 'Amount received (F3)' : 'Amount received'"
                @input="recalc"
                @wheel.prevent
              />
              <button
                @click="form.amount_paid = total"
                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-300 transition-colors shrink-0"
              >Exact</button>
            </div>
            <div class="flex gap-1 mt-1">
              <button
                v-for="denom in quickDenominations"
                :key="denom"
                @click="form.amount_paid = denom"
                class="flex-1 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors"
              >{{ denom >= 1000 ? (denom / 1000) + 'K' : denom }}</button>
            </div>
          </div>

          <!-- Change / balance (shown for both modes) -->
          <div class="px-3 pb-1.5">
            <div v-if="changeDue > 0" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-2.5 py-1">
              <span class="text-xs font-semibold text-green-700">Change</span>
              <span class="text-base font-bold text-green-600">LKR {{ lkr(changeDue) }}</span>
            </div>
            <div v-else-if="balanceDue > 0.009" class="flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-lg px-2.5 py-1">
              <span class="text-xs font-semibold text-yellow-700">Balance due</span>
              <span class="text-base font-bold text-yellow-600">LKR {{ lkr(balanceDue) }}</span>
            </div>
          </div>

          <!-- Error -->
          <p v-if="error" class="mx-3 mb-1 text-xs text-red-600 bg-red-50 border border-red-200 px-2.5 py-1.5 rounded-lg flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ error }}
          </p>

          <!-- Action buttons -->
          <div class="flex gap-2 px-3 pb-2.5 pt-1">
            <button
              @click="submit('draft')"
              :disabled="saving || !form.items.filter(i => i.product_id).length"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-gray-500 hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-semibold text-sm transition-colors"
            >
              <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
              <CheckCircleIcon v-else class="w-4 h-4" />
              Draft <span v-if="kbShortcutsEnabled" class="opacity-60 text-xs font-normal ml-0.5">F9</span>
            </button>
            <button
              @click="submit('completed')"
              :disabled="saving || !form.items.filter(i => i.product_id).length"
              class="flex-[2] flex items-center justify-center gap-2 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-bold text-sm shadow-md transition-colors"
            >
              <ArrowPathIcon v-if="saving" class="w-5 h-5 animate-spin" />
              <CheckCircleIcon v-else class="w-5 h-5" />
              {{ saving ? 'Processing…' : 'Complete Sale' }} <span v-if="kbShortcutsEnabled" class="opacity-60 text-xs font-normal ml-0.5">F10</span>
            </button>
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

          <div class="col-span-2 text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1 mt-3">Payment</div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Switch to Cash</span>
            <div class="flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Alt</kbd>
              <span class="text-gray-400 text-xs">+</span>
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">C</kbd>
            </div>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Switch to Card</span>
            <div class="flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Alt</kbd>
              <span class="text-gray-400 text-xs">+</span>
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">D</kbd>
            </div>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Toggle split payment</span>
            <div class="flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Alt</kbd>
              <span class="text-gray-400 text-xs">+</span>
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">S</kbd>
            </div>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">Set exact amount</span>
            <div class="flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Alt</kbd>
              <span class="text-gray-400 text-xs">+</span>
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">E</kbd>
            </div>
          </div>
          <div class="flex items-center justify-between py-1 border-b border-gray-50">
            <span class="text-gray-700">New bill</span>
            <div class="flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">Alt</kbd>
              <span class="text-gray-400 text-xs">+</span>
              <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">N</kbd>
            </div>
          </div>
          <div class="flex items-center justify-between py-1">
            <span class="text-gray-700">Toggle this help</span>
            <kbd class="px-2 py-0.5 rounded bg-gray-100 border border-gray-300 text-xs font-mono font-bold">?</kbd>
          </div>
        </div>
        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
          + / − keys only work when no input field is focused. Alt shortcuts work anytime.
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
  ArrowLeftIcon, PlusIcon, XMarkIcon, TrashIcon,
  ShoppingCartIcon, CheckCircleIcon, ArrowPathIcon,
  ExclamationTriangleIcon, QrCodeIcon, MagnifyingGlassIcon, ShoppingBagIcon,
  QuestionMarkCircleIcon,
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

// Product browser state
const activeCategory    = ref('All')
const productGridSearch = ref('')
const gridFocusIndex    = ref(-1)
const GRID_COLS = { base: 3, xl: 4, '2xl': 5 }

// Form
const form = reactive({
  customer_id: '', payment_method: 'cash', payment_status: 'paid',
  discount: 0, tax: 0, tax_rate: 0, amount_paid: 0, notes: '',
  table_number: '', status: 'completed', card_reference: '',
  items: [],
})
const selectedTaxId  = ref('')
const discountType   = ref('fixed') // 'fixed' | 'percent'
const discountInput  = ref(0)

// Split payment state
const splitPayment        = ref(false)
const splitCash           = ref(0)
const splitCard           = ref(0)
const splitCardReference  = ref('')

// Keyboard shortcut refs
const searchInputRef   = ref(null)
const barcodeInputRef  = ref(null)
const amountInputRef   = ref(null)
const showKbHelp       = ref(false)
const kbShortcutsEnabled = ref(localStorage.getItem('pos_keyboard_shortcuts') !== 'false')

// UI state
const saving              = ref(false)
const error               = ref('')
const showNewCustomer     = ref(false)
const savingCustomer      = ref(false)
const newCustomerError    = ref('')
const newCustomer         = reactive({ name: '', phone: '', email: '' })
const showPricingDetails  = ref(false)

// Barcode
const barcodeInput = ref('')
const barcodeError = ref('')
let barcodeClearTimer = null

// Open bottle picker
const openBottlePicker = reactive({ show: false, item: null, bottles: [], loading: false })

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
  // Price = proportion of remaining volume × full bottle selling price
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

// Scanner
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

const paymentOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Card' },
]

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
  }
  return list
})

watch(gridProducts, () => { gridFocusIndex.value = -1 })

const selectedCustomer = computed(() => customers.value.find(c => c.id == form.customer_id) ?? null)

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
  if (isStockTracked(product) && product.stock_quantity < 1) return
  const isLiquor = LIQUOR_TYPES.includes(String(product?.product_type ?? '').toLowerCase())
    || ['Hard Liquor', 'Foreign Liquor'].includes(product?.category?.name)
  // Liquor products always get a new line so each shot can have its own serving_ml
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
  }
}

function addItem()      { form.items.push(newItem()) }
function removeItem(i)  { form.items.splice(i, 1); recalc() }
function clearCart()    { if (form.items.some(i => i.product_id)) { form.items = []; recalc() } }

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
  form.amount_paid = total.value
}

function applyTax() {
  const t = taxes.value.find(x => x.id == selectedTaxId.value)
  if (t) { form.tax_rate = t.rate; recalc() }
  else   { form.tax_rate = 0; form.tax = 0 }
}

function fillProduct(item) {
  const p = products.value.find(x => x.id == item.product_id)
  if (!p) { item.product_ref = null; return }
  item.product_ref           = p
  item.product_search        = p.name || ''
  item.unit_price            = p.selling_price
  item.serving_ml            = 0
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
  selectedTaxId.value       = ''
  discountType.value        = 'fixed'
  discountInput.value       = 0
  error.value               = ''
  showPricingDetails.value  = false
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
    form.items = (data.items || []).map(si => ({
      product_id:            si.product_id,
      product_search:        si.product?.name || '',
      quantity:              Number(si.quantity || 0),
      unit_price:            Number(si.product?.selling_price ?? si.unit_price ?? 0),
      discount:              Number(si.discount || 0),
      serving_ml:            Number(si.serving_ml || 0),
      empty_bottle_returned: false,
      bottle_deposit_amount: Number(si.product?.bottle_deposit_amount || 0),
      product_ref:           si.product,
      _lineTotal:            0,
    }))
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
    return
  }
  if (isStockTracked(product) && product.stock_quantity < 1) {
    barcodeError.value = `"${product.name}" is out of stock`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }
  addProductFromGrid(product)
  barcodeError.value = ''
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
        serving_ml:            i.serving_ml,
        open_bottle_id:        i.open_bottle_id || null,
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

  // ── F-keys: work regardless of focus ──
  if (e.key === 'F1') {
    e.preventDefault()
    searchInputRef.value?.focus()
    searchInputRef.value?.select()
    return
  }
  if (e.key === 'F2') {
    e.preventDefault()
    barcodeInputRef.value?.focus()
    barcodeInputRef.value?.select()
    return
  }
  if (e.key === 'F3') {
    e.preventDefault()
    amountInputRef.value?.focus()
    amountInputRef.value?.select()
    return
  }
  if (e.key === 'F9') {
    e.preventDefault()
    if (!saving.value && form.items.some(i => i.product_id)) submit('draft')
    return
  }
  if (e.key === 'F10') {
    e.preventDefault()
    if (!saving.value && form.items.some(i => i.product_id)) submit('completed')
    return
  }

  // ── Escape: close modals / blur ──
  if (e.key === 'Escape') {
    if (openBottlePicker.show) { openBottlePicker.show = false; return }
    if (scannerOpen.value)     { closeScanner(); return }
    if (showKbHelp.value)      { showKbHelp.value = false; return }
    document.activeElement?.blur()
    return
  }

  // ── Delete: clear cart (only when not typing in an input) ──
  if (e.key === 'Delete' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
    e.preventDefault()
    clearCart()
    return
  }

  // ── Alt+key combos: work even when typing ──
  if (e.altKey) {
    switch (e.key.toLowerCase()) {
      case 'c':
        e.preventDefault()
        form.payment_method = 'cash'
        splitPayment.value = false
        break
      case 'd':
        e.preventDefault()
        form.payment_method = 'card'
        splitPayment.value = false
        break
      case 's':
        e.preventDefault()
        toggleSplit()
        break
      case 'e':
        e.preventDefault()
        if (splitPayment.value) {
          splitCash.value = total.value
          splitCard.value = 0
        } else {
          form.amount_paid = total.value
        }
        break
      case 'n':
        e.preventDefault()
        resetForm()
        break
    }
    return
  }

  // ── Non-input shortcuts: only fire when not typing ──
  // Allow arrow keys from the product search input to enter grid navigation
  const fromSearch = document.activeElement === searchInputRef.value
  if (fromSearch && ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(e.key)) {
    // fall through to grid nav below
  } else if (isTypingInInput()) return

  // ── Product grid arrow key navigation ──
  const gridLen = gridProducts.value.length
  if (gridLen > 0 && ['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Enter'].includes(e.key)) {
    const cols = window.innerWidth >= 1536 ? 5 : window.innerWidth >= 1280 ? 4 : 3
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

  if (e.key === '?') {
    e.preventDefault()
    showKbHelp.value = !showKbHelp.value
    return
  }
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

  // Default to Liquor category if it exists
  const hasLiquor = products.value.some(p => p.category?.name === 'Liquor')
  if (hasLiquor) activeCategory.value = 'Liquor'

  // Auto-load a specific draft when coming from the Edit button
  const draftId = route.query.draft
  if (draftId) {
    const draft = draftBills.value.find(d => d.id == draftId)
    if (draft) {
      await loadDraft(draft)
    } else {
      // Draft not in the list (e.g. different page) — fetch it directly
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
