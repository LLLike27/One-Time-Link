<template>
  <div class="blank-layout">
    <router-view />
  </div>
</template>

<style scoped>
.blank-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--app-container-padding);
  background: transparent;
}
</style>
