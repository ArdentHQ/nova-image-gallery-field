<template>
  <div class="relative inline-flex items-center m-1">
    <img :src="thumbnail" class="w-16 h-16 object-cover object-center inline rounded-xl pr-1" />
    <div class="bg-primary-500 text-white text-xs font-bold absolute inline-flex items-center justify-center w-5 h-5 rounded-full -top-2 -right-1">{{ count }}</div>
  </div>
</template>

<script>
export default {
  props: ['resourceName', 'field'],
  computed: {
    count() {
      return (this.field.value && this.field.value.length)?this.field.value.length:'-';
    },
    thumbnail() {
      return (this.field.value && this.field.value.length)?this.field.value[0].url:'';
    }
  }
}
</script>
