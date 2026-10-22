<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<!-- Light LMS mark: a four-point spark. -->
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    class={className}
    {...rest}
>
    <path
        fill="currentColor"
        d="M12 1.5c.5 0 .9.3 1 .8l1.4 5.4c.2.8.8 1.4 1.6 1.6l5.4 1.4c.5.1.8.5.8 1s-.3.9-.8 1l-5.4 1.4c-.8.2-1.4.8-1.6 1.6L13 20.1c-.1.5-.5.8-1 .8s-.9-.3-1-.8l-1.4-5.4c-.2-.8-.8-1.4-1.6-1.6l-5.4-1.4c-.5-.1-.8-.5-.8-1s.3-.9.8-1L8 8.3c.8-.2 1.4-.8 1.6-1.6L11 1.3c.1-.5.5-.8 1-.8Z"
    />
</svg>
