<template>
    <div style="position: relative; height:300px;">
      <canvas ref="canvasEl"></canvas>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
  } from 'chart.js'
  
  // Daftarkan controller + elemen + scale + plugin Chart.js
  Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend)
  
  const props = defineProps<{
    data: { nama_produk: string; volume: number }[]
  }>()
  
  const canvasEl = ref<HTMLCanvasElement | null>(null)
  let chartInstance: Chart | null = null
  
  function renderChart() {
    if (!canvasEl.value) return
  
    const labels = props.data.map(r => r.nama_produk)
    const volumes = props.data.map(r => r.volume)
  
    // Jika chart sudah ada, hancurkan dulu
    if (chartInstance) {
      chartInstance.destroy()
    }
  
    chartInstance = new Chart(canvasEl.value, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Volume Stok',
            data: volumes,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor:   'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            hoverBackgroundColor: 'rgba(59, 130, 246, 0.75)',
            hoverBorderColor: 'rgba(59, 130, 246, 1)'
          }
        ]
      },
      options: {
        animation: {
          duration: 800,
          easing: 'easeOutQuart'
        },
        scales: {
          x: {
            ticks: { color: '#334155', font: { size: 12 } }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: '#334155',
              font: { size: 12 },
              callback: function (value) {
                if (typeof value === 'number') {
                  return value.toLocaleString('id-ID')
                }
                return value
              }
            }
          }
        },
        plugins: {
          legend: {
            position: 'top' as const,
            labels: { color: '#1e293b', font: { size: 12 } }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const v = ctx.parsed.y
                return `${v.toLocaleString('id-ID')} unit`
              }
            }
          }
        },
        responsive: true,
        maintainAspectRatio: false
      }
    })
  }
  
  onMounted(renderChart)
  watch(
    () => props.data,
    () => {
      renderChart()
    },
    { deep: true }
  )
  </script>
  