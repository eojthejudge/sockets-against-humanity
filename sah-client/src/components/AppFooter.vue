<template>
  <footer>
    <p>Copyright &copy; {{ year }}</p>
  </footer>
</template>
<script>
export default {
  computed: {
    year() {
      const year = new Date().getFullYear();
      return  year;
    },
  },
};
</script>