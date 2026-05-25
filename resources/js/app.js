import ACPSalesGuide    from './components/ACPSalesGuide.vue'
import ACPSalesGuideIndex from './views/ACPSalesGuideIndex.vue'
import FollowupsToday    from './views/FollowupsToday.vue'
import SalesContentSetup from './views/SalesContentSetup.vue'

if (window.Innoclapps) {
  Innoclapps.booting((app, router) => {
    // Register panel component (injected into deal detail page)
    app.component('ACPSalesGuide', ACPSalesGuide)

    router.addRoute({
      path:      '/acp-sales-guide',
      name:      'acp-sales-guide',
      component: ACPSalesGuideIndex,
      meta:      { title: 'ACP Sales Guide' },
    })

    router.addRoute({
      path:      '/acp-sales-guide/today',
      name:      'acp-followups-today',
      component: FollowupsToday,
      meta:      { title: "Today's Follow-ups" },
    })

    router.addRoute({
      path:      '/acp-sales-guide/sales-content',
      name:      'acp-sales-content',
      component: SalesContentSetup,
      meta:      { title: 'Sales Content Setup' },
    })
  })
}
