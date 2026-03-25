(function () {
  function initGallery(root) {
    var slides = Array.prototype.slice.call(root.querySelectorAll('.tharchive-gallery__slide'))
    var thumbs = Array.prototype.slice.call(root.querySelectorAll('.tharchive-gallery__thumb'))
    var prevButton = root.querySelector('[data-gallery-nav="prev"]')
    var nextButton = root.querySelector('[data-gallery-nav="next"]')

    if (slides.length <= 1) {
      return
    }

    var currentIndex = slides.findIndex(function (slide) {
      return slide.classList.contains('is-active')
    })

    if (currentIndex < 0) {
      currentIndex = 0
    }

    function update(index) {
      currentIndex = index

      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === currentIndex)
      })

      thumbs.forEach(function (thumb, thumbIndex) {
        var isActive = thumbIndex === currentIndex
        thumb.classList.toggle('is-active', isActive)
        thumb.setAttribute('aria-current', isActive ? 'true' : 'false')
      })
    }

    function move(step) {
      var nextIndex = currentIndex + step

      if (nextIndex < 0) {
        nextIndex = slides.length - 1
      } else if (nextIndex >= slides.length) {
        nextIndex = 0
      }

      update(nextIndex)
    }

    thumbs.forEach(function (thumb, index) {
      thumb.addEventListener('click', function () {
        update(index)
      })
    })

    if (prevButton) {
      prevButton.addEventListener('click', function () {
        move(-1)
      })
    }

    if (nextButton) {
      nextButton.addEventListener('click', function () {
        move(1)
      })
    }

    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault()
        move(-1)
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault()
        move(1)
      }
    })

    update(currentIndex)
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tharchive-gallery="1"]').forEach(initGallery)
  })
})()
