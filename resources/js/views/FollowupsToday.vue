<template>
  <MainLayout>
  <div class="mx-auto max-w-5xl space-y-8">
    <div>
      <ICardHeader>
        <div>
          <ICardHeading text="Today's Follow-ups" />
          <IText
            class="block"
            :text="'Showing all pending follow-ups for today — ' + todayLabel"
          />
        </div>

        <ICardActions>
          <IButton variant="white" @click="fetchFollowups">
            Refresh
          </IButton>
        </ICardActions>
      </ICardHeader>

      <ICard :overlay="loading">
        <template v-if="!loading">
          <div v-if="followups.length" class="px-6">
            <ITable class="[--gutter:theme(spacing.6)]" bleed>
              <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
                <ITableRow>
                  <ITableHeader>Deal</ITableHeader>
                  <ITableHeader>Contact</ITableHeader>
                  <ITableHeader>Phone</ITableHeader>
                  <ITableHeader>Type</ITableHeader>
                  <ITableHeader>Time</ITableHeader>
                  <ITableHeader>Note</ITableHeader>
                  <ITableHeader width="14%" />
                </ITableRow>
              </ITableHead>

              <ITableBody>
                <ITableRow v-for="f in followups" :key="f.id">
                  <ITableCell class="font-medium">
                    <ILink
                      :to="{ name: 'view-deal', params: { id: f.deal_id } }"
                      :text="f.deal_name"
                    />
                  </ITableCell>

                  <ITableCell>{{ f.contact_name }}</ITableCell>

                  <ITableCell>
                    <span v-if="f.phone" class="font-mono text-sm">
                      {{ f.phone }}
                    </span>
                    <span v-else class="text-neutral-400">—</span>
                  </ITableCell>

                  <ITableCell>
                    <IBadge
                      :variant="f.followup_type === 'whatsapp' ? 'success' : 'info'"
                      class="capitalize"
                    >
                      {{ f.followup_type }}
                    </IBadge>
                  </ITableCell>

                  <ITableCell>{{ f.followup_time }}</ITableCell>

                  <ITableCell class="max-w-xs truncate text-neutral-500 dark:text-neutral-400">
                    {{ f.note || '—' }}
                  </ITableCell>

                  <ITableCell>
                    <div class="flex items-center gap-1.5">
                      <!-- Open Deal -->
                      <IButton
                        small
                        variant="white"
                        :to="{ name: 'view-deal', params: { id: f.deal_id } }"
                      >
                        Open
                      </IButton>

                      <!-- WhatsApp (only for whatsapp type) -->
                      <IButton
                        v-if="f.followup_type === 'whatsapp' && f.phone"
                        small
                        variant="success"
                        soft
                        @click="openWhatsApp(f)"
                      >
                        WhatsApp
                      </IButton>

                      <!-- Mark Done -->
                      <IButton
                        small
                        variant="primary"
                        soft
                        @click="markDone(f)"
                      >
                        Done
                      </IButton>
                    </div>
                  </ITableCell>
                </ITableRow>
              </ITableBody>
            </ITable>
          </div>

          <ICardBody v-else>
            <IEmptyState
              title="No follow-ups for today"
              description="All pending follow-ups scheduled for today will appear here."
            />
          </ICardBody>
        </template>
      </ICard>
    </div>
  </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import MainLayout from '@/Core/components/MainLayout.vue'

const followups = ref([])
const loading   = ref(true)

const todayLabel = new Date().toLocaleDateString('en-IN', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
})

async function fetchFollowups() {
  loading.value = true
  try {
    const { data } = await Innoclapps.request().get('/akash-sales-pipeline/followups/today')
    followups.value = data
  } catch (e) {
    Innoclapps.error('Failed to load today\'s follow-ups.')
  } finally {
    loading.value = false
  }
}

async function markDone(f) {
  try {
    await Innoclapps.request().put(`/akash-sales-pipeline/followups/${f.id}/today-done`)
    followups.value = followups.value.filter(x => x.id !== f.id)
    Innoclapps.success('Follow-up marked as done.')
  } catch (e) {
    Innoclapps.error('Failed to update follow-up.')
  }
}

function openWhatsApp(f) {
  if (f.whatsapp_message) {
    window.open(
      `https://wa.me/${f.phone}?text=${encodeURIComponent(f.whatsapp_message)}`,
      '_blank'
    )
  } else {
    window.open(`https://wa.me/${f.phone}`, '_blank')
  }
}

onMounted(fetchFollowups)
</script>
