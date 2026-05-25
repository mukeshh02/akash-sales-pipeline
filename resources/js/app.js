import AkashSalesGuide from './components/AkashSalesGuide.vue'
import AkashSalesPipelineIndex from './views/AkashSalesPipelineIndex.vue'
import FollowupsToday from './views/FollowupsToday.vue'
import SalesContentSetup from './views/SalesContentSetup.vue'

if (window.Innoclapps) {
  Innoclapps.booting((app, router) => {
    // Register panel component globally (injected into deal detail view)
    app.component('AkashSalesGuide', AkashSalesGuide)

    // Module settings & mapping page
    router.addRoute({
      path:      '/akash-sales-pipeline',
      name:      'akash-sales-pipeline',
      component: AkashSalesPipelineIndex,
      meta:      { title: 'Akash Sales Pipeline' },
    })

    // Today's follow-ups dashboard
    router.addRoute({
      path:      '/akash-sales-pipeline/today',
      name:      'akash-followups-today',
      component: FollowupsToday,
      meta:      { title: "Today's Follow-ups" },
    })

    // Sales Content Setup
    router.addRoute({
      path:      '/akash-sales-pipeline/sales-content',
      name:      'akash-sales-content',
      component: SalesContentSetup,
      meta:      { title: 'Sales Content Setup' },
    })
  })
}
