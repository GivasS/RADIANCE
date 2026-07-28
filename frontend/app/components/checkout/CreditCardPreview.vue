<template>
  <div class="card-scene" :class="{ flipped: isFlipped }">
    <div class="card-3d">
      <!-- FRENTE -->
      <div class="card-face card-front" :style="cardGradient">
        <div class="card-shine" />
        <div class="card-pattern" />

        <div class="absolute top-5 right-5 h-8 flex items-center">
          <Transition name="brand-fade" mode="out-in">
            <img v-if="brand" :key="brand" :src="`/images/brands/${brand}.svg`" class="h-8 object-contain drop-shadow" :alt="brand">
          </Transition>
        </div>

        <div class="chip">
          <div class="chip-line chip-line-h" />
          <div class="chip-line chip-line-v" />
          <div class="chip-center" />
        </div>

        <div class="card-number">
          <span v-for="(group, i) in numberGroups" :key="i" class="number-group">
            <span v-for="(char, j) in group" :key="j" class="number-char" :class="{ 'number-dot': char === '•' }">
              {{ char }}
            </span>
          </span>
        </div>

        <div class="card-bottom">
          <div class="card-holder">
            <div class="card-label">Titular</div>
            <div class="card-value name-value">{{ displayName || 'SEU NOME AQUI' }}</div>
          </div>
          <div class="card-expiry-box">
            <div class="card-label">Validade</div>
            <div class="card-value">{{ displayExpiry || 'MM/AAAA' }}</div>
          </div>
        </div>
      </div>

      <!-- VERSO -->
      <div class="card-face card-back" :style="cardGradient">
        <div class="card-shine" />
        <div class="card-pattern" />
        <div class="mag-stripe" />

        <div class="cvv-area">
          <div class="cvv-strip">
            <span class="cvv-label">CVV</span>
            <span class="cvv-value">{{ cvvDisplay }}</span>
          </div>
        </div>

        <div class="absolute bottom-5 right-5 opacity-60">
          <Transition name="brand-fade" mode="out-in">
            <img v-if="brand" :key="brand" :src="`/images/brands/${brand}.svg`" class="h-6 object-contain" :alt="brand">
          </Transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  number?: string
  name?: string
  expiry?: string
  cvv?: string
  brand?: string
  isFlipped?: boolean
}>()

// Gradiente por bandeira (cores reais das bandeiras); default usa a paleta da Radiance.
const brandGradients: Record<string, string> = {
  visa: 'linear-gradient(135deg, #1a1f71 0%, #0057a8 60%, #003087 100%)',
  mastercard: 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #eb001b 100%)',
  elo: 'linear-gradient(135deg, #f9c31a 0%, #f0a800 60%, #e09000 100%)',
  amex: 'linear-gradient(135deg, #007bc1 0%, #005a8e 60%, #003d6b 100%)',
  hipercard: 'linear-gradient(135deg, #b7121f 0%, #8b0d17 60%, #6b0a12 100%)',
  diners: 'linear-gradient(135deg, #2c2c2c 0%, #4a4a4a 60%, #1a1a1a 100%)',
  default: 'linear-gradient(135deg, #7D1F49 0%, #A0275C 50%, #D94E85 100%)',
}

const cardGradient = computed(() => ({ background: brandGradients[props.brand ?? ''] || brandGradients.default }))

const numberGroups = computed(() => {
  const raw = (props.number ?? '').replace(/\s/g, '').padEnd(16, '•')
  return [raw.slice(0, 4).split(''), raw.slice(4, 8).split(''), raw.slice(8, 12).split(''), raw.slice(12, 16).split('')]
})

const displayName = computed(() => props.name ? props.name.toUpperCase().slice(0, 26) : '')
const displayExpiry = computed(() => props.expiry ?? '')
const cvvDisplay = computed(() => props.cvv ? '•'.repeat(props.cvv.length) : '•••')
</script>

<style scoped>
.card-scene {
  width: 100%;
  max-width: 400px;
  aspect-ratio: 1.586 / 1;
  perspective: 1200px;
  margin: 0 auto;
}

.card-3d {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.65s cubic-bezier(0.4, 0.2, 0.2, 1);
}

.card-scene.flipped .card-3d {
  transform: rotateY(180deg);
}

.card-face {
  position: absolute;
  inset: 0;
  border-radius: 16px;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  overflow: hidden;
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.25),
    0 8px 20px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.card-back {
  transform: rotateY(180deg);
}

.card-shine {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.08) 40%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.08) 60%, rgba(255,255,255,0) 100%);
  pointer-events: none;
  z-index: 1;
}

.card-pattern {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 20% 80%, rgba(255,255,255,0.06) 0%, transparent 40%),
    radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 40%);
  pointer-events: none;
}

.chip {
  position: absolute;
  top: 38%;
  left: 6%;
  width: 13%;
  aspect-ratio: 1.4 / 1;
  background: linear-gradient(135deg, #d4a843 0%, #f0c96b 40%, #b8892a 60%, #e8b84b 100%);
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.chip-line {
  position: absolute;
  background: rgba(160, 110, 20, 0.6);
}

.chip-line-h { width: 80%; height: 1px; top: 50%; }
.chip-line-v { height: 80%; width: 1px; left: 50%; }

.chip-center {
  width: 35%;
  height: 35%;
  background: linear-gradient(135deg, #c8962a, #e8b84b);
  border-radius: 2px;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.3);
}

.card-number {
  position: absolute;
  bottom: 28%;
  left: 6%;
  right: 6%;
  display: flex;
  gap: 10px;
  justify-content: space-between;
  z-index: 2;
}

.number-group { display: flex; gap: 3px; }

.number-char {
  color: white;
  font-family: 'Courier New', monospace;
  font-size: clamp(12px, 3.2vw, 18px);
  font-weight: 600;
  letter-spacing: 0.05em;
  text-shadow: 0 1px 3px rgba(0,0,0,0.5);
  transition: opacity 0.2s;
}

.number-dot { opacity: 0.5; }

.card-bottom {
  position: absolute;
  bottom: 7%;
  left: 6%;
  right: 6%;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  z-index: 2;
}

.card-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: clamp(7px, 1.5vw, 9px);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.card-value {
  color: white;
  font-family: 'Courier New', monospace;
  font-size: clamp(10px, 2.2vw, 13px);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-shadow: 0 1px 3px rgba(0,0,0,0.5);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

.card-expiry-box { text-align: right; }

.mag-stripe {
  position: absolute;
  top: 15%;
  left: 0;
  right: 0;
  height: 18%;
  background: linear-gradient(180deg, #111 0%, #222 50%, #111 100%);
  box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.cvv-area {
  position: absolute;
  top: 40%;
  left: 6%;
  right: 6%;
  z-index: 2;
}

.cvv-strip {
  background: white;
  border-radius: 4px;
  padding: 6px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cvv-label {
  color: #666;
  font-size: clamp(8px, 1.8vw, 10px);
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.cvv-value {
  color: #333;
  font-family: 'Courier New', monospace;
  font-size: clamp(12px, 2.8vw, 16px);
  font-weight: 700;
  letter-spacing: 0.2em;
}

.brand-fade-enter-active, .brand-fade-leave-active {
  transition: all 0.3s ease;
}
.brand-fade-enter-from { opacity: 0; transform: scale(0.7) translateY(-4px); }
.brand-fade-leave-to { opacity: 0; transform: scale(0.7) translateY(4px); }
</style>
