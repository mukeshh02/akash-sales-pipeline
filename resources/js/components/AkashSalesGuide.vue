<template>
  <Panel :panel="panel">

    <!-- Panel heading -->
    <ITextDisplay class="mb-4">
      {{ panel?.heading ?? 'Sales Guide' }}
    </ITextDisplay>

    <!-- ── Loading skeleton ─────────────────────────────────────── -->
    <template v-if="fetchingGuide || guide.is_mapped === undefined">
      <div class="mb-4 space-y-2">
        <div class="h-4 w-2/3 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700" />
        <div class="h-4 w-1/2 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700" />
        <div class="h-8 w-full animate-pulse rounded bg-neutral-200 dark:bg-neutral-700" />
      </div>
    </template>

    <template v-else>
      
      <!-- ── Stage Title ─────────────────────────────────────────── -->
      <div class="mb-4 flex items-start justify-between gap-2">
        <div>
          <span class="text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
            Current CRM Stage
          </span>
          <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">
            {{ guide.stage_name || 'Unknown' }}
          </h3>
        </div>
        <IBadge v-if="isFollowupStage && followupSummary.done_count > 0" variant="warning" class="shrink-0 mt-1">
          Follow-up #{{ followupSummary.done_count }}
        </IBadge>
      </div>

      <!-- ── Follow-up Stage Mode (Always active for Follow-up stages) ── -->
      <template v-if="isFollowupStage">
        <!-- Progress Counter Badge inside the panel if needed -->
        <div class="mb-4 flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800">
          <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Follow-up History</span>
          <IBadge v-if="followupSummary.done_count > 0" variant="warning">
            #{{ followupSummary.done_count }} Done
          </IBadge>
          <span v-else class="text-[10px] text-neutral-400 italic">No attempts yet</span>
        </div>

        <!-- Has a pending follow-up → show it as primary focus -->
        <div v-if="followupSummary.next" class="mb-6">
          <div class="rounded-xl border border-warning-200 bg-warning-50/40 p-4 dark:border-warning-500/20 dark:bg-warning-500/5 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-warning-600 dark:text-warning-400 mb-2 block">
              Next Scheduled Action
            </span>
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <IBadge :variant="followupSummary.next.followup_type === 'whatsapp' ? 'success' : 'info'" class="capitalize">
                {{ followupSummary.next.followup_type }}
              </IBadge>
              <span class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                {{ followupSummary.next.display_dt }}
              </span>
            </div>
            <p v-if="followupSummary.next.note" class="text-xs text-neutral-500 dark:text-neutral-400 mb-3 italic">
              {{ followupSummary.next.note }}
            </p>
            <div class="flex gap-2">
              <IButton
                v-if="followupSummary.next.followup_type === 'whatsapp'"
                variant="success"
                size="sm"
                @click="sendFollowupWhatsApp(followupSummary.next)"
              >
                <span class="mr-1">📱</span> Send WhatsApp
              </IButton>
              <IButton variant="primary" size="sm" @click="markDone(followupSummary.next)">
                ✓ Mark Done
              </IButton>
            </div>
          </div>
        </div>

        <!-- No pending follow-up → prompt to schedule the next one -->
        <div v-else class="mb-6">
          <div class="rounded-xl border-2 border-dashed border-warning-300 dark:border-warning-500/30 p-4 text-center">
            <p class="text-xs font-semibold text-warning-700 dark:text-warning-400 mb-1">No follow-up scheduled</p>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-3">Keep the momentum going by scheduling the next touchpoint.</p>
            <IButton variant="warning" size="sm" @click="openAddFollowup">
              + Schedule Next Follow-up
            </IButton>
          </div>
        </div>

        <!-- Outcome prompt shown after marking a follow-up done -->
        <div v-if="showOutcomeFor" class="mb-6 rounded-xl border border-success-200 bg-success-50/40 p-4 dark:border-success-500/20 dark:bg-success-500/5">
          <p class="text-xs font-bold uppercase tracking-widest text-success-700 dark:text-success-400 mb-2">
            What was the result?
          </p>
          <div class="flex flex-wrap gap-2">
            <IButton size="sm" variant="success" @click="handleOutcome('responded')">
              ✓ They Responded
            </IButton>
            <IButton size="sm" variant="white" @click="handleOutcome('reschedule')">
              📅 No Answer — Reschedule
            </IButton>
            <IButton size="sm" variant="danger" soft @click="handleOutcome('not_interested')">
              ✗ Not Interested
            </IButton>
          </div>
        </div>
      </template>

      <!-- ── Stage not mapped ──────────────────────────────────── -->
      <div
        v-else-if="guide.is_mapped === false"
        class="mb-6 rounded-md bg-warning-50 p-3 dark:bg-warning-900/20"
      >
        <p class="text-xs font-semibold text-warning-800 dark:text-warning-200">
          ⚠ Stage not configured
        </p>
        <p class="mt-1 text-xs text-warning-700 dark:text-warning-300">
          This stage is not yet linked to any sales tools in Akash Sales Pipeline.
        </p>
        <ILink :to="{ name: 'akash-sales-pipeline' }" plain class="mt-1 block text-xs">
          Configure stage tools →
        </ILink>
      </div>

      <!-- ── Visual Sequence Designer UI (For other stages) ──────── -->
      <template v-else>
          <div v-if="nextPendingItem" class="rounded-xl border border-primary-100 bg-primary-50/30 p-4 dark:border-primary-500/20 dark:bg-primary-500/5 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-primary-500 mb-1 block">
              Suggested Next Action
            </span>
            <h4 class="text-sm font-bold text-neutral-900 dark:text-white mb-3">
               {{ nextPendingItem.key }}
            </h4>

            <div v-if="nextPendingItem.is_template" class="space-y-3">
               <div class="rounded-lg bg-white p-3 text-xs italic text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300 border border-neutral-100 dark:border-neutral-700 shadow-sm">
                 "{{ nextPendingItem.message }}"
               </div>
               <IButton
                  block
                  variant="success"
                  size="lg"
                  @click="sendChecklistWhatsApp(nextPendingItem)"
                >
                  <span class="mr-2">📱</span> Send on WhatsApp
                </IButton>
            </div>
            
            <IButton
              v-else
              block
              variant="primary"
              size="lg"
              @click="toggleChecklistItem(nextPendingItem.key)"
            >
              ✓ Mark as Done
            </IButton>
          </div>

        <!-- Other Tools & Checklist Items -->
        <div class="space-y-4">
          
          <!-- Static Tools (Scripts, Samples, etc.) -->
          <div v-if="sc.script || (sc.links && sc.links.length) || sc.documents" class="space-y-3">
             <p class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Additional Tools</p>
             
             <IButton v-if="sc.script" block variant="white" size="sm" @click="openScriptModal">
                📋 View Call Script
             </IButton>
             
             <IButton v-if="sc.documents" block variant="white" size="sm" @click="openEstimateModal">
                📄 Proposals &amp; Quotes
             </IButton>
          </div>

          <!-- Collapsed History -->
          <div v-if="sc.checklist.some(i => i.is_completed)" class="mt-4">
            <button 
              class="text-[10px] font-bold uppercase tracking-widest text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300"
              @click="showCompletedTasks = !showCompletedTasks"
            >
              {{ showCompletedTasks ? 'Hide' : 'View' }} History ({{ sc.checklist.filter(i => i.is_completed).length }} done)
            </button>
            
            <div v-if="showCompletedTasks" class="mt-2 space-y-2 opacity-60">
              <div
                v-for="item in sc.checklist.filter(i => i.is_completed)"
                :key="item.key"
                class="flex items-center justify-between rounded border border-neutral-100 bg-neutral-50 p-2 dark:border-neutral-800 dark:bg-neutral-900/30"
              >
                <div class="flex items-center gap-2">
                  <span class="text-success-500 text-xs">✓</span>
                  <span class="text-xs text-neutral-500 line-through">{{ item.key }}</span>
                </div>
                <IButton 
                  v-if="item.is_template" 
                  size="sm" 
                  variant="white" 
                  soft 
                  @click="sendChecklistWhatsApp(item)"
                >Resend</IButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Note (Shared for all tools) -->
        <div class="mt-4 border-t border-neutral-100 pt-4 dark:border-neutral-800">
          <button
            v-if="!showNote"
            class="text-xs text-neutral-400 underline hover:text-neutral-600 dark:hover:text-neutral-300"
            @click="showNote = true"
          >
            + Add context/note (optional)
          </button>
          <IFormTextarea
            v-else
            v-model="activityNote"
            rows="2"
            placeholder="Add context or outcome details…"
            class="mt-1"
          />
        </div>

      </template>
    </template>

    <!-- ── Divider ───────────────────────────────────────────────── -->
    <hr class="-mx-5 my-5 border-neutral-200 dark:border-neutral-500/30">

    <!-- ── Follow-ups ────────────────────────────────────────────── -->
    <div>

      <IButton
        variant="success"
        block
        :loading="sendingFollowupWhatsApp"
        class="mb-3"
        @click="sendFollowupWhatsApp(followupSummary.next)"
      >
        Send Follow-up on WhatsApp
      </IButton>

      <IButton variant="white" block small class="mb-4" @click="openAddFollowup">
        + Schedule Follow-up
      </IButton>

      <!-- Upcoming -->
      <div v-if="followups.upcoming.length" class="mb-4">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
          Upcoming
        </p>
        <ul class="space-y-2">
          <li
            v-for="f in followups.upcoming"
            :key="f.id"
            class="rounded-md border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-800"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <IBadge variant="info" class="shrink-0 capitalize">{{ f.followup_type }}</IBadge>
                  <span class="text-xs text-neutral-600 dark:text-neutral-400">{{ f.display_dt }}</span>
                </div>
                <p v-if="f.note" class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ f.note }}</p>
              </div>
              <div class="flex shrink-0 gap-1">
                <IButton
                  v-if="f.followup_type === 'whatsapp'"
                  variant="success"
                  soft
                  small
                  @click="sendFollowupWhatsApp(f)"
                >WhatsApp</IButton>
                <IButton small @click="markDone(f)">Done</IButton>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Past -->
      <div v-if="followups.past.length">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
          Past
        </p>
        <ul class="space-y-2">
          <li
            v-for="f in followups.past"
            :key="f.id"
            class="rounded-md border border-neutral-200 p-3 dark:border-neutral-700"
          >
            <div class="flex flex-wrap items-center gap-2">
              <IBadge variant="neutral" class="shrink-0 capitalize">{{ f.followup_type }}</IBadge>
              <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ f.display_dt }}</span>
              <IBadge variant="success" class="shrink-0">Done</IBadge>
            </div>
            <p v-if="f.note" class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ f.note }}</p>
          </li>
        </ul>
      </div>

      <p
        v-if="!followups.upcoming.length && !followups.past.length"
        class="text-sm text-neutral-400 dark:text-neutral-500"
      >
        No follow-ups scheduled yet.
      </p>

    </div>

  </Panel>

  <!-- ═══════════════════════════════════════════════════════════════
       Call Script Modal  (New Lead)
       IModal uses v-model:visible — NOT v-model.
       ═══════════════════════════════════════════════════════════════ -->
  <IModal
    v-model:visible="showScriptModal"
    title="Call Script"
    size="md"
    hide-footer
  >
    <div v-if="sc.script" class="space-y-4">
      <div
        class="max-h-80 overflow-y-auto rounded border border-neutral-200 bg-neutral-50 p-4 text-sm
               text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
        style="white-space: pre-wrap"
      >{{ sc.script }}</div>

      <IFormGroup label="Note (Optional)" label-for="script-note">
        <IFormTextarea
          id="script-note"
          v-model="activityNote"
          rows="2"
          placeholder="Add outcome or context…"
        />
      </IFormGroup>

      <div class="flex gap-2">
        <IButton variant="primary" :loading="logging" @click="logActivity('call')">
          ✓ Log Call Made
        </IButton>
        <IButton basic @click="showScriptModal = false">Close</IButton>
      </div>
    </div>

    <div
      v-else
      class="rounded-md border border-dashed border-neutral-300 p-4 dark:border-neutral-600"
    >
      <p class="text-sm text-neutral-500">No call script configured yet.</p>
      <ILink :to="{ name: 'akash-sales-content' }" plain class="mt-1 block text-sm">
        Set it up in Sales Content Setup →
      </ILink>
    </div>
  </IModal>

  <!-- ═══════════════════════════════════════════════════════════════
       Proposals & Quotes Modal  (Estimate Shared)
       ═══════════════════════════════════════════════════════════════ -->
  <IModal
    v-model:visible="showEstimateModal"
    title="Proposals &amp; Quotes"
    size="md"
    hide-footer
  >
    <div class="space-y-4">

      <!-- Loading skeletons -->
      <div v-if="fetchingDocuments" class="space-y-2">
        <div class="h-14 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700" />
        <div class="h-14 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700" />
      </div>

      <!-- No documents yet -->
      <div
        v-else-if="!dealDocuments.length"
        class="rounded-md border border-dashed border-neutral-300 p-4 dark:border-neutral-600"
      >
        <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
          No Proposal or Quote created yet.
        </p>
        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
          Use the <strong>Documents</strong> tab on this deal page to create one —
          the deal and contact are pre-filled automatically.
        </p>

        <!-- Fallback: send estimate template via WhatsApp if a message exists -->
        <div v-if="sc.message && guide.contact_phone" class="mt-3">
          <p class="mb-2 text-xs text-neutral-500">
            Or send your estimate text via WhatsApp:
          </p>
          <div
            class="mb-2 max-h-24 overflow-y-auto rounded border border-neutral-200 bg-neutral-50 p-2 text-xs
                   text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
            style="white-space: pre-wrap"
          >{{ sc.message }}</div>
          <IButton small variant="success" :loading="logging" @click="sendWhatsApp">
            Send on WhatsApp
          </IButton>
        </div>

        <div class="mt-3">
          <IButton small variant="primary" soft @click="goCreateDocument">
            Open Documents Tab
          </IButton>
        </div>
      </div>

      <!-- Documents list -->
      <div v-else class="space-y-2">
        <div
          v-for="doc in dealDocuments"
          :key="doc.id"
          class="rounded-md border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-800"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-neutral-800 dark:text-white">
                {{ doc.title }}
              </p>
              <div class="mt-1 flex flex-wrap items-center gap-2">
                <span v-if="doc.type" class="text-xs text-neutral-500 dark:text-neutral-400">
                  {{ doc.type }}
                </span>
                <IBadge :variant="docStatusVariant(doc.status)">
                  {{ docStatusLabel(doc.status) }}
                </IBadge>
                <span class="text-xs text-neutral-400">{{ doc.updated_at }}</span>
              </div>
            </div>
            <div class="flex shrink-0 flex-col gap-1.5 sm:flex-row">
              <IButton
                variant="success"
                soft
                small
                :disabled="!guide.contact_phone"
                @click="sendDocumentViaWhatsApp(doc)"
              >
                WhatsApp
              </IButton>
              <IButton small @click="openDocument(doc)">Open</IButton>
            </div>
          </div>
        </div>

        <IButton small variant="white" block @click="goCreateDocument">
          + Create Another Document
        </IButton>
      </div>

      <p
        v-if="!guide.contact_phone"
        class="flex items-center gap-1 text-xs text-danger-600 dark:text-danger-400"
      >
        <span>⚠</span>
        <span>No phone number on contact — WhatsApp buttons are disabled.</span>
      </p>

    </div>
  </IModal>

  <!-- ═══════════════════════════════════════════════════════════════
       Schedule Follow-up Modal
       ═══════════════════════════════════════════════════════════════ -->
  <IModal
    v-model:visible="showFollowupModal"
    title="Schedule Follow-up"
    ok-text="Save"
    @ok="saveFollowup"
  >
    <div class="space-y-4">
      <IFormGroup label="Date" label-for="fu-date" required>
        <IFormInput id="fu-date" v-model="followupForm.followup_date" type="date" />
      </IFormGroup>
      <IFormGroup label="Time (Optional)" label-for="fu-time">
        <IFormInput id="fu-time" v-model="followupForm.followup_time" type="time" />
      </IFormGroup>
      <IFormGroup label="Type" label-for="fu-type" required>
        <IFormSelect v-model="followupForm.followup_type" id="fu-type">
          <option value="call">Call</option>
          <option value="whatsapp">WhatsApp</option>
        </IFormSelect>
      </IFormGroup>
      <IFormGroup label="Message Template" label-for="fu-template">
        <IFormSelect v-model="followupForm.template_name" id="fu-template">
          <option :value="null">-- Select Pre-made Message (1, 2, 3...) --</option>
          <option
            v-for="tpl in guide.templates"
            :key="tpl.name"
            :value="tpl.name"
          >
            {{ tpl.name }}
          </option>
        </IFormSelect>
      </IFormGroup>
      <IFormGroup label="Note (Optional)" label-for="fu-note">
        <IFormTextarea
          id="fu-note"
          v-model="followupForm.note"
          placeholder="What to discuss or send…"
        />
      </IFormGroup>
    </div>
  </IModal>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps({
  resourceId:   { type: Number, required: true },
  resourceName: { type: String, required: true },
  resource:     { type: Object, default: null  },
  panel:        { type: Object, default: null  },
})

// ── Document status helpers ────────────────────────────────────────
const DOC_STATUS = {
  draft:    { variant: 'neutral', label: 'Draft'    },
  sent:     { variant: 'info',    label: 'Sent'     },
  accepted: { variant: 'success', label: 'Accepted' },
  lost:     { variant: 'danger',  label: 'Lost'     },
}
const docStatusVariant = s => DOC_STATUS[s]?.variant ?? 'neutral'
const docStatusLabel   = s => DOC_STATUS[s]?.label   ?? s

// ── Guide state ────────────────────────────────────────────────────
const fetchingGuide = ref(false)
const guide = ref({
  stage_name:    null,
  is_mapped:     undefined,   // undefined = not yet loaded
  last_action:   null,
  contact_phone: '',
  toolbox:       null,
  templates:     [],
})

// Shorthand for toolbox (with safe defaults)
const sc = computed(() =>
  guide.value.toolbox ?? { script: null, links: [], message: null, documents: false, checklist: [] }
)

// The next action to focus on in the sequence
const nextPendingIndex = computed(() =>
  sc.value.checklist?.findIndex(item => !item.is_completed) ?? -1
)

const nextPendingItem = computed(() =>
  nextPendingIndex.value !== -1 ? sc.value.checklist[nextPendingIndex.value] : null
)

// ── Follow-up Stage Mode ───────────────────────────────────────────
const isFollowupStage = computed(() =>
  guide.value.stage_name?.toLowerCase().includes('follow') ?? false
)

const followupSummary = computed(() =>
  guide.value.followup_summary ?? { done_count: 0, has_upcoming: false, next: null }
)

const showOutcomeFor = ref(null)

function handleOutcome(outcome) {
  if (outcome === 'reschedule') {
    openAddFollowup()
  } else if (outcome === 'responded') {
    Innoclapps.success('Great! Move this deal to the next stage when ready.')
  } else if (outcome === 'not_interested') {
    Innoclapps.info("Mark this deal as Lost in the pipeline when ready.")
  }
  showOutcomeFor.value = null
}

// ── Activity logging state ─────────────────────────────────────────
const logging      = ref(false)
const activityNote = ref('')
const showNote     = ref(false)

// ── Modal visibility ───────────────────────────────────────────────
const showScriptModal   = ref(false)
const showEstimateModal = ref(false)
const showCompletedTasks = ref(false)

function openScriptModal() {
  activityNote.value  = ''
  showScriptModal.value = true
}

function openEstimateModal() {
  showEstimateModal.value = true
  fetchDealDocuments()
}

// ── Fetch guide from backend ───────────────────────────────────────
async function fetchGuide() {
  fetchingGuide.value = true
  try {
    const { data } = await Innoclapps.request().get(
      `/akash-sales-pipeline/deals/${props.resourceId}/guide`
    )
    guide.value = data
  } catch (e) {
    console.error('Sales guide fetch failed', e)
    } finally {
    fetchingGuide.value = false
  }
}

// ── Toggle checklist item ──────────────────────────────────────────
async function toggleChecklistItem(itemKey) {
  try {
    const { data } = await Innoclapps.request().post(
      `/akash-sales-pipeline/deals/${props.resourceId}/guide/checklist-toggle`,
      { item_key: itemKey, stage_id: props.resource?.stage_id }
    )
    guide.value = data
  } catch (e) {
    console.error('Checklist toggle failed', e)
    Innoclapps.error('Failed to update task.')
  }
}

async function sendChecklistWhatsApp(item) {
  if (!guide.value.contact_phone) {
    Innoclapps.error('No phone number found for this contact.')
    return
  }
  
  // 1. Open WhatsApp
  window.open(
    `https://wa.me/${guide.value.contact_phone}?text=${encodeURIComponent(item.message)}`,
    '_blank'
  )

  // 2. Automatically mark as completed (if not already)
  if (!item.is_completed) {
    await toggleChecklistItem(item.key)
    Innoclapps.success(`Sent ${item.key} and marked as completed.`)
  }
}

// ── Log an activity ────────────────────────────────────────────────
// Sends ONLY action_type + optional note.
// Backend determines the current stage from the CRM — never from frontend.
async function logActivity(actionType) {
  logging.value = true
  try {
    const { data } = await Innoclapps.request().post(
      `/akash-sales-pipeline/deals/${props.resourceId}/guide`,
      { action_type: actionType, note: activityNote.value || null }
    )
    guide.value         = data
    activityNote.value  = ''
    showNote.value      = false
    showScriptModal.value   = false
    showEstimateModal.value = false
    Innoclapps.success('Activity logged.')
  } catch (e) {
    console.error('logActivity failed', e)
    Innoclapps.error('Failed to log activity. Please try again.')
  } finally {
    logging.value = false
  }
}

// ── Send WhatsApp using the step template ──────────────────────────
async function sendWhatsApp() {
  if (!guide.value.contact_phone) {
    Innoclapps.error('No phone number found. Add a phone number to the contact first.')
    return
  }
  if (!sc.value.message) {
    Innoclapps.error('No WhatsApp template configured for this step. Set it up in Sales Content Setup.')
    return
  }
  window.open(
    `https://wa.me/${guide.value.contact_phone}?text=${encodeURIComponent(sc.value.message)}`,
    '_blank'
  )
  await logActivity('whatsapp')
}

// ── Deal Documents (Estimate Shared step) ─────────────────────────
const dealDocuments     = ref([])
const fetchingDocuments = ref(false)

async function fetchDealDocuments() {
  fetchingDocuments.value = true
  try {
    const { data } = await Innoclapps.request().get(
      `/akash-sales-pipeline/deals/${props.resourceId}/documents`
    )
    dealDocuments.value = data
  } catch (e) {
    console.error('Failed to load deal documents', e)
  } finally {
    fetchingDocuments.value = false
  }
}

async function sendDocumentViaWhatsApp(doc) {
  if (!guide.value.contact_phone) {
    Innoclapps.error('No phone number found for this contact.')
    return
  }
  const contactName = props.resource?.contacts?.[0]?.display_name
                   ?? props.resource?.contacts?.[0]?.name
                   ?? 'there'
  const message = `Hi ${contactName}, please find your ${doc.type} here:\n${doc.public_url}`
  window.open(
    `https://wa.me/${guide.value.contact_phone}?text=${encodeURIComponent(message)}`,
    '_blank'
  )
  try {
    const { data } = await Innoclapps.request().post(
      `/akash-sales-pipeline/deals/${props.resourceId}/guide`,
      { action_type: 'whatsapp', note: `Document link sent via WhatsApp: ${doc.title}` }
    )
    guide.value = data
    Innoclapps.success('Document link sent via WhatsApp and logged.')
  } catch (e) {
    console.error('Document WhatsApp log failed', e)
  }
}

function openDocument(doc) {
  Innoclapps.request().post(
    `/akash-sales-pipeline/deals/${props.resourceId}/guide`,
    { action_type: 'estimate', note: `Document opened: ${doc.title}` }
  ).catch(console.error)
  showEstimateModal.value = false
  router.push({ name: 'view-document', params: { id: doc.id } })
}

function goCreateDocument() {
  Innoclapps.request().post(
    `/akash-sales-pipeline/deals/${props.resourceId}/guide`,
    { action_type: 'estimate', note: 'Navigated to create a new document' }
  ).catch(console.error)
  showEstimateModal.value = false
  router.push({ name: 'create-document' })
}

// ── Quick follow-up WhatsApp (always-visible section) ─────────────
const sendingFollowupWhatsApp = ref(false)

async function sendFollowupWhatsApp(followup = null) {
  sendingFollowupWhatsApp.value = true
  try {
    const templateParam = followup?.template_name ? `?template=${followup.template_name}` : ''
    const { data } = await Innoclapps.request().get(
      `/akash-sales-pipeline/deals/${props.resourceId}/followup-whatsapp${templateParam}`
    )
    if (!data.has_phone) {
      Innoclapps.error('No phone number found. Add a phone number to the contact first.')
      return
    }
    if (!data.has_template) {
      Innoclapps.error('No active follow-up template found. Add one in Sales Content Setup.')
      return
    }
    window.open(
      `https://wa.me/${data.phone}?text=${encodeURIComponent(data.message)}`,
      '_blank'
    )
    await Innoclapps.request().post(
      `/akash-sales-pipeline/deals/${props.resourceId}/guide`,
      { action_type: 'whatsapp', note: 'Follow-up WhatsApp sent' }
    )
    await fetchGuide()
    Innoclapps.success('WhatsApp follow-up opened and logged.')
  } catch (e) {
    console.error('Follow-up WhatsApp failed', e)
    Innoclapps.error('Something went wrong. Please try again.')
  } finally {
    sendingFollowupWhatsApp.value = false
  }
}

// ── Scheduled follow-ups ───────────────────────────────────────────
const followups        = ref({ upcoming: [], past: [] })
const showFollowupModal = ref(false)
const followupForm = reactive({
  followup_date: '',
  followup_time: '',
  followup_type: 'call',
  template_name: null,
  note: '',
})

async function fetchFollowups() {
  try {
    const { data } = await Innoclapps.request().get(
      `/akash-sales-pipeline/deals/${props.resourceId}/followups`
    )
    followups.value = data
  } catch (e) {
    console.error('fetchFollowups failed', e)
  }
}

function openAddFollowup() {
  followupForm.followup_date = ''
  followupForm.followup_time = ''
  followupForm.followup_type = 'call'
  followupForm.template_name = null
  followupForm.note          = ''
  showFollowupModal.value    = true
}

async function saveFollowup() {
  if (!followupForm.followup_date) {
    Innoclapps.error('Please select a date.')
    return
  }
  try {
    const { data } = await Innoclapps.request().post(
      `/akash-sales-pipeline/deals/${props.resourceId}/followups`,
      { ...followupForm }
    )
    showFollowupModal.value = false
    await fetchFollowups()
    await fetchGuide()
    Innoclapps.success('Follow-up scheduled.')
  } catch (e) {
    console.error('saveFollowup failed', e)
    Innoclapps.error('Failed to save follow-up.')
  }
}

async function markDone(followup) {
  try {
    await Innoclapps.request().put(
      `/akash-sales-pipeline/followups/${followup.id}/done`
    )
    showOutcomeFor.value = followup.id
    await fetchFollowups()
    await fetchGuide()
    Innoclapps.success('Marked as completed.')
  } catch (e) {
    console.error('markDone failed', e)
    Innoclapps.error('Failed to update follow-up.')
  }
}

// ── Lifecycle ──────────────────────────────────────────────────────
onMounted(() => {
  fetchGuide()
  fetchFollowups()
})

watch(() => props.resourceId, () => {
  fetchGuide()
  fetchFollowups()
})
</script>
