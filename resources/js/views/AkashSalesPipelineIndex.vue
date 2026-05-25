<template>
  <MainLayout>
  <div class="mx-auto max-w-6xl space-y-8">

    <!-- ── Stage Mapping ──────────────────────────────────────────── -->
    <div>
      <ICardHeader>
        <div>
          <ICardHeading text="Stage Guide Toolbox Configuration" />
          <IText
            class="block"
            text="Enable specific tools for each CRM pipeline stage. These tools will appear in the Sales Guide panel for deals in that stage."
          />
        </div>
      </ICardHeader>

      <ICard as="form" :overlay="mappingLoading" @submit.prevent="saveMappings">
        <template v-if="pipelines.length">
          <div v-for="(pipeline, pIndex) in pipelines" :key="pipeline.id">
            <!-- Pipeline name separator -->
            <div class="px-6 pb-2 pt-5">
              <p
                class="text-sm font-semibold text-neutral-700 dark:text-neutral-200"
              >
                {{ pipeline.name }}
              </p>
            </div>

            <div class="px-6 pb-6">
              <ITable class="[--gutter:theme(spacing.6)]" bleed>
                <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
                  <ITableRow>
                    <ITableHeader width="20%">CRM Stage</ITableHeader>
                    <ITableHeader width="45%">Enabled Tools (Toolbox)</ITableHeader>
                    <ITableHeader>Stage Checklist (comma-separated)</ITableHeader>
                  </ITableRow>
                </ITableHead>

                <ITableBody>
                  <ITableRow
                    v-for="stage in pipeline.stages"
                    :key="stage.id"
                  >
                    <ITableCell class="font-medium">{{ stage.name }}</ITableCell>
                    <ITableCell>
                      <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                        
                        <!-- Call Script -->
                        <IFormCheckbox 
                          v-model="stage.config.show_script" 
                          label="Call Script" 
                        />

                        <!-- Work Samples -->
                        <IFormCheckbox 
                          v-model="stage.config.show_samples" 
                          label="Work Samples" 
                        />

                        <!-- Documents -->
                        <IFormCheckbox 
                          v-model="stage.config.show_documents" 
                          label="Proposals / Quotes" 
                        />

                        <!-- WhatsApp Template -->
                        <div class="flex items-center gap-2 border-l border-neutral-200 pl-4 dark:border-neutral-700">
                          <span class="text-xs text-neutral-500">WhatsApp:</span>
                          <IFormSelect v-model="stage.config.whatsapp_template" class="w-40" size="sm">
                            <option :value="null">None</option>
                            <option
                              v-for="tpl in availableTemplates"
                              :key="tpl.name"
                              :value="tpl.name"
                            >
                              {{ tpl.name }}
                            </option>
                          </IFormSelect>
                        </div>

                      </div>
                    </ITableCell>
                    <ITableCell>
                      <!-- Sequence Chips -->
                      <div class="flex flex-wrap items-center gap-2">
                        <div 
                          v-for="(item, idx) in stage.config.checklist" 
                          :key="idx"
                          class="group flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 border border-primary-200 dark:border-primary-500/20 shadow-sm"
                        >
                          <span class="text-[10px] text-primary-400 font-bold">{{ idx + 1 }}</span>
                          <span>{{ item }}</span>
                          <button 
                            type="button"
                            class="ml-1 hidden group-hover:block text-primary-400 hover:text-primary-600 dark:hover:text-primary-200"
                            @click="removeSequenceItem(stage, idx)"
                          >
                            ×
                          </button>
                        </div>

                        <!-- Add Action Dropdown -->
                        <div class="relative inline-block text-left">
                          <IFormSelect 
                            class="!w-36 !py-0.5 !text-[11px]" 
                            @input="(value) => addSequenceItem(stage, value)"
                            :model-value="null"
                          >
                            <option :value="null" disabled>+ Add Follow-up</option>
                            <template v-if="availableTemplates.length">
                              <option
                                v-for="tpl in availableTemplates"
                                :key="tpl.name"
                                :value="tpl.name"
                              >
                                {{ tpl.name }}
                              </option>
                            </template>
                            <option v-else disabled>No Active Templates Found</option>
                          </IFormSelect>
                        </div>
                      </div>
                    </ITableCell>
                  </ITableRow>
                </ITableBody>
              </ITable>
            </div>

            <hr
              v-if="pIndex < pipelines.length - 1"
              class="border-t border-neutral-200 dark:border-neutral-500/30"
            />
          </div>
        </template>

        <ICardFooter class="text-right">
          <IButton
            type="submit"
            variant="primary"
            :loading="mappingSaving"
            text="Save Visual Sequences"
          />
        </ICardFooter>
      </ICard>
    </div>

    <!-- ── WhatsApp Templates ─────────────────────────────────────── -->
    <div>
      <ICardHeader>
        <ICardHeading text="WhatsApp Message Templates" />
      </ICardHeader>

      <ICard>
        <div class="px-6">
          <ITable class="[--gutter:theme(spacing.6)]" bleed>
            <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
              <ITableRow>
                <ITableHeader>Name</ITableHeader>
                <ITableHeader>Content Preview</ITableHeader>
                <ITableHeader>Status</ITableHeader>
                <ITableHeader width="8%" />
              </ITableRow>
            </ITableHead>

            <ITableBody>
              <ITableRow
                v-for="template in templates"
                :key="template.id"
              >
                <ITableCell class="capitalize font-medium">
                  {{ template.name }}
                </ITableCell>

                <ITableCell class="max-w-sm truncate text-neutral-500 dark:text-neutral-400">
                  {{ template.content }}
                </ITableCell>

                <ITableCell>
                  <IBadge :variant="template.is_active ? 'success' : 'neutral'">
                    {{ template.is_active ? 'Active' : 'Disabled' }}
                  </IBadge>
                </ITableCell>

                <ITableCell>
                  <ITableRowActions>
                    <ITableRowAction
                      text="Edit"
                      @click="editTemplate(template)"
                    />
                  </ITableRowActions>
                </ITableCell>
              </ITableRow>
            </ITableBody>
          </ITable>
        </div>
      </ICard>
    </div>

  </div>
  </MainLayout>

  <!-- ── Edit Template Modal ───────────────────────────────────────── -->
  <IModal
    v-model:visible="showEditModal"
    :title="'Edit Template: ' + editingTemplate.name"
    ok-text="Save Changes"
    @ok="updateTemplate"
  >
    <IFormGroup label="Template Content" label-for="tpl-content">
      <IFormTextarea
        id="tpl-content"
        v-model="editingTemplate.content"
        rows="6"
        placeholder="Enter template message…"
      />

      <IFormText
        class="mt-1"
        text="Available: {name} {phone} {event_date} {event_type} {budget} {package} {salesman_name} {company_name}"
      />
    </IFormGroup>

    <IFormGroup class="mt-3">
      <IFormCheckbox v-model="editingTemplate.is_active" label="Is Active" />
    </IFormGroup>
  </IModal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MainLayout from '@/Core/components/MainLayout.vue'

// ── Stage Mapping ─────────────────────────────────────────────────
const pipelines           = ref([])
const availableTemplates  = ref([])
const mappingLoading      = ref(true)
const mappingSaving       = ref(false)

async function fetchMappings() {
  mappingLoading.value = true
  try {
    const { data } = await Innoclapps.request().get('/akash-sales-pipeline/stage-mappings')
    
    // Ensure every stage has a config object
    data.pipelines.forEach(p => {
      p.stages.forEach(s => {
        if (!s.config) {
          s.config = {
            show_script: false,
            show_samples: false,
            show_documents: false,
            whatsapp_template: null,
            checklist: [],
          }
        }
      })
    })
    
    pipelines.value          = data.pipelines
    availableTemplates.value = data.templates
  } finally {
    mappingLoading.value = false
  }
}

function addSequenceItem(stage, templateName) {
  if (!templateName) return
  if (!stage.config.checklist) stage.config.checklist = []
  stage.config.checklist.push(templateName)
}

function removeSequenceItem(stage, index) {
  stage.config.checklist.splice(index, 1)
}

async function saveMappings() {
  mappingSaving.value = true
  try {
    const mappings = pipelines.value.flatMap(pipeline =>
      pipeline.stages.map(stage => ({
        pipeline_id: pipeline.id,
        stage_id:    stage.id,
        config:      stage.config,
      }))
    )
    await Innoclapps.request().post('/akash-sales-pipeline/stage-mappings', { mappings })
    Innoclapps.success('Visual sequences saved successfully')
  } catch {
    Innoclapps.error('Failed to save configuration')
  } finally {
    mappingSaving.value = false
  }
}

// ── Templates ────────────────────────────────────────────────────
const templates       = ref([])
const showEditModal   = ref(false)
const editingTemplate = ref({ id: null, name: '', content: '', is_active: true })

async function fetchTemplates() {
  const { data } = await Innoclapps.request().get('/akash-sales-pipeline/templates')
  templates.value = data
}

function editTemplate(template) {
  editingTemplate.value = { ...template }
  showEditModal.value = true
}

async function updateTemplate() {
  await Innoclapps.request().put(
    `/akash-sales-pipeline/templates/${editingTemplate.value.id}`,
    { content: editingTemplate.value.content, is_active: editingTemplate.value.is_active }
  )
  await fetchTemplates()
  await fetchMappings() // refresh template list in mapping dropdown
  showEditModal.value = false
  Innoclapps.success('Template updated successfully')
}

onMounted(() => {
  fetchMappings()
  fetchTemplates()
})
</script>
