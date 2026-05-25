<template>
  <MainLayout>
  <div class="mx-auto max-w-5xl space-y-8">

    <div>
      <ICardHeader>
        <div>
          <ICardHeading text="Sales Content Setup" />
          <IText
            class="block"
            text="Configure the call script, sample links, and WhatsApp message templates shown inside each deal's Next Step modal."
          />
        </div>
      </ICardHeader>

      <ICard as="form" :overlay="loading" @submit.prevent="save">
        <ICardBody>

          <!-- â”€â”€ Call Script â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <p class="mb-3 text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Call Script
          </p>

          <IFormGroup label-for="call_script">
            <IFormTextarea
              id="call_script"
              v-model="settings.call_script"
              rows="6"
              placeholder="Hi, my name is [name] from [company]â€¦"
            />
            <IFormText
              text="Shown in the Next Step modal when current step is 'New Lead'. Read this script when calling the client."
            />
          </IFormGroup>

          <hr class="-mx-7 my-5 border-t border-neutral-200 dark:border-neutral-500/30" />

          <!-- â”€â”€ Sample Links â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <p class="mb-3 text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Work Sample Links
          </p>
          <IFormText
            class="mb-4"
            text="These links appear in the 'Sample Shared' step modal so the team can quickly share them with clients."
          />

          <IFormGroup label="Website" label-for="website_link">
            <IFormInput
              id="website_link"
              v-model="settings.website_link"
              type="url"
              placeholder="https://yourwebsite.com"
            />
          </IFormGroup>

          <IFormGroup label="PDF Portfolio" label-for="pdf_portfolio_link">
            <IFormInput
              id="pdf_portfolio_link"
              v-model="settings.pdf_portfolio_link"
              type="url"
              placeholder="https://drive.google.com/â€¦"
            />
          </IFormGroup>

          <IFormGroup label="Client Reviews" label-for="client_review_link">
            <IFormInput
              id="client_review_link"
              v-model="settings.client_review_link"
              type="url"
              placeholder="https://g.page/â€¦ or https://testimonialsâ€¦"
            />
          </IFormGroup>

          <hr class="-mx-7 my-5 border-t border-neutral-200 dark:border-neutral-500/30" />

          <!-- â”€â”€ WhatsApp Templates â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
          <p class="mb-1 text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            WhatsApp Message Templates
          </p>
          <IFormText
            class="mb-4"
            text="Available placeholders: {name} {phone} {event_date} {budget} {salesman_name} {company_name}"
          />

          <IFormGroup label="Intro Message â€” shown at 'Contact Made' step" label-for="tpl_intro">
            <IFormTextarea
              id="tpl_intro"
              v-model="templateMap.intro"
              rows="4"
              placeholder="Hi {name}, this is {salesman_name} from {company_name}â€¦"
            />
          </IFormGroup>

          <IFormGroup label="Sample Message â€” shown at 'Sample Shared' step" label-for="tpl_sample">
            <IFormTextarea
              id="tpl_sample"
              v-model="templateMap.sample"
              rows="4"
              placeholder="Hi {name}, please check our portfolioâ€¦"
            />
          </IFormGroup>

          <IFormGroup label="Estimate Message â€” shown at 'Estimate Shared' step" label-for="tpl_estimate">
            <IFormTextarea
              id="tpl_estimate"
              v-model="templateMap.estimate"
              rows="4"
              placeholder="Hi {name}, here is the estimate for your eventâ€¦"
            />
          </IFormGroup>

          <IFormGroup label="Follow-up Message â€” shown at 'Negotiation' &amp; 'Follow-up' steps" label-for="tpl_followup">
            <IFormTextarea
              id="tpl_followup"
              v-model="templateMap.followup"
              rows="4"
              placeholder="Hi {name}, just following up on our conversationâ€¦"
            />
          </IFormGroup>

        </ICardBody>

        <ICardFooter class="text-right">
          <IButton
            type="submit"
            variant="primary"
            :loading="saving"
            text="Save"
          />
        </ICardFooter>
      </ICard>
    </div>

  </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import MainLayout from '@/Core/components/MainLayout.vue'

const loading = ref(true)
const saving  = ref(false)

// Flat objects for easy v-model binding
const settings    = reactive({
  call_script:        '',
  website_link:       '',
  pdf_portfolio_link: '',
  client_review_link: '',
})

// template content keyed by name
const templateMap  = reactive({ intro: '', sample: '', estimate: '', followup: '' })
// keep IDs so we can send them back
const templateIds  = reactive({ intro: null, sample: null, estimate: null, followup: null })

async function fetchContent() {
  loading.value = true
  try {
    const { data } = await Innoclapps.request().get('/acp-sales-guide/sales-content')

    // Fill settings
    for (const row of data.settings) {
      if (row.key in settings) {
        settings[row.key] = row.value ?? ''
      }
    }

    // Fill templates
    for (const tpl of data.templates) {
      if (tpl.name in templateMap) {
        templateMap[tpl.name] = tpl.content ?? ''
        templateIds[tpl.name] = tpl.id
      }
    }
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await Innoclapps.request().post('/acp-sales-guide/sales-content', {
      settings: Object.entries(settings).map(([key, value]) => ({
        key,
        value: value || null,
      })),
      templates: Object.entries(templateMap).map(([name, content]) => ({
        id:      templateIds[name],
        content: content || '',
      })),
    })
    Innoclapps.success('Sales content saved successfully.')
  } catch (e) {
    Innoclapps.error('Failed to save. Please try again.')
  } finally {
    saving.value = false
  }
}

onMounted(fetchContent)
</script>

